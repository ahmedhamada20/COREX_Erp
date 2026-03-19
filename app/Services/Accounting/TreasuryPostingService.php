<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Treasuries;
use App\Models\TreasuriesDelivery;
use Illuminate\Support\Facades\DB;

class TreasuryPostingService
{
    public function postDelivery(TreasuriesDelivery $delivery): JournalEntry
    {
        return DB::transaction(function () use ($delivery) {

            // ✅ Lock delivery row to avoid double posting
            $delivery = TreasuriesDelivery::query()
                ->where('id', $delivery->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! empty($delivery->journal_entry_id)) {
                return JournalEntry::query()
                    ->where('user_id', (int) $delivery->user_id)
                    ->findOrFail((int) $delivery->journal_entry_id);
            }

            $ownerId = (int) $delivery->user_id;

            [$lines, $desc, $source] = $this->buildLinesFromDelivery($delivery);

            // ✅ Safety: must be balanced
            $totalDebit = $this->sumDebit($lines);
            $totalCredit = $this->sumCredit($lines);

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new \RuntimeException("Unbalanced lines for delivery #{$delivery->id}");
            }

            // ✅ Sequential Entry Number (locked)
            $entryNumber = $this->nextEntryNumberLocked($ownerId);

            $entry = JournalEntry::create([
                'user_id' => $ownerId,
                'entry_number' => $entryNumber,
                'entry_date' => $delivery->doc_date ?? now()->toDateString(),
                'source' => $source, // treasury_delivery
                'reference_type' => TreasuriesDelivery::class,
                'reference_id' => $delivery->id,
                'description' => $desc,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => (int) ($delivery->actor_user_id ?? auth()->id() ?? 0),
            ]);

            $lineNo = 1;
            foreach ($lines as $l) {
                JournalEntryLine::create([
                    'user_id' => $ownerId,
                    'journal_entry_id' => $entry->id,
                    'account_id' => (int) $l['account_id'],
                    'debit' => (float) ($l['debit'] ?? 0),
                    'credit' => (float) ($l['credit'] ?? 0),
                    'memo' => $l['memo'] ?? null,
                    'line_no' => $lineNo++,
                ]);
            }

            // ✅ Link delivery -> entry (posted)
            $delivery->update([
                'journal_entry_id' => $entry->id,
            ]);

            // ✅ Apply to balances AFTER writing lines (موحد)
            $this->applyEntryToBalancesAtomic($entry);

            // Final Safety
            if (method_exists($entry, 'isBalanced') && ! $entry->isBalanced()) {
                throw new \RuntimeException("Journal entry not balanced for delivery #{$delivery->id}");
            }

            return $entry;
        });
    }

    public function repostOnUpdate(TreasuriesDelivery $delivery): JournalEntry
    {
        return DB::transaction(function () use ($delivery) {

            $delivery = TreasuriesDelivery::query()
                ->where('id', $delivery->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! empty($delivery->journal_entry_id)) {
                $this->reverseByEntryId(
                    (int) $delivery->journal_entry_id,
                    (int) $delivery->user_id,
                    "Repost delivery #{$delivery->id}"
                );

                // unlink to post fresh
                $delivery->update(['journal_entry_id' => null]);
            }

            return $this->postDelivery($delivery);
        });
    }

    public function reverseOnDelete(TreasuriesDelivery $delivery, ?string $reason = null): ?JournalEntry
    {
        return DB::transaction(function () use ($delivery, $reason) {

            $delivery = TreasuriesDelivery::query()
                ->where('id', $delivery->id)
                ->lockForUpdate()
                ->first();

            if (! $delivery || empty($delivery->journal_entry_id)) {
                return null;
            }

            return $this->reverseByEntryId(
                (int) $delivery->journal_entry_id,
                (int) $delivery->user_id,
                $reason ?: "Reverse delivery #{$delivery->id}"
            );
        });
    }

    /* =========================
     * Internal helpers
     * ========================= */

    private function buildLinesFromDelivery(TreasuriesDelivery $delivery): array
    {
        $type = (string) $delivery->type;
        $amount = (float) $delivery->amount;

        if ($amount <= 0) {
            throw new \InvalidArgumentException("Invalid amount for delivery #{$delivery->id}");
        }

        $source = 'treasury_delivery';

        $fromTreasury = $delivery->from_treasury_id
            ? Treasuries::query()->where('user_id', (int) $delivery->user_id)->findOrFail($delivery->from_treasury_id)
            : null;

        $toTreasury = $delivery->to_treasury_id
            ? Treasuries::query()->where('user_id', (int) $delivery->user_id)->findOrFail($delivery->to_treasury_id)
            : null;

        $fromAcc = $fromTreasury?->account_id ? (int) $fromTreasury->account_id : null;
        $toAcc = $toTreasury?->account_id ? (int) $toTreasury->account_id : null;

        $counterpartyAcc = $delivery->counterparty_account_id ? (int) $delivery->counterparty_account_id : null;

        $receipt = $delivery->receipt_no ? (' إيصال #'.$delivery->receipt_no) : '';
        $reason = $delivery->reason ? (' - '.$delivery->reason) : '';
        $desc = "حركة خزنة: {$type}{$receipt}{$reason}";

        $lines = [];

        if ($type === 'transfer') {
            if (! $fromAcc || ! $toAcc) {
                throw new \RuntimeException('Transfer requires from/to treasury accounts');
            }
            if ($fromAcc === $toAcc) {
                throw new \RuntimeException('Transfer cannot be between same account');
            }

            $lines[] = ['account_id' => $toAcc,   'debit' => $amount, 'credit' => 0,       'memo' => 'تحويل وارد'];
            $lines[] = ['account_id' => $fromAcc, 'debit' => 0,       'credit' => $amount, 'memo' => 'تحويل صادر'];

            return [$lines, $desc, $source];
        }

        if ($type === 'collection') {
            if (! $toAcc) {
                throw new \RuntimeException('Collection requires to_treasury account');
            }
            if (! $counterpartyAcc) {
                throw new \RuntimeException('Collection requires counterparty_account_id');
            }

            $lines[] = ['account_id' => $toAcc,           'debit' => $amount, 'credit' => 0,       'memo' => 'قبض خزنة'];
            $lines[] = ['account_id' => $counterpartyAcc, 'debit' => 0,       'credit' => $amount, 'memo' => 'طرف مقابل قبض'];

            return [$lines, $desc, $source];
        }

        if ($type === 'payment') {
            if (! $fromAcc) {
                throw new \RuntimeException('Payment requires from_treasury account');
            }
            if (! $counterpartyAcc) {
                throw new \RuntimeException('Payment requires counterparty_account_id');
            }

            $lines[] = ['account_id' => $counterpartyAcc, 'debit' => $amount, 'credit' => 0,       'memo' => 'طرف مقابل صرف'];
            $lines[] = ['account_id' => $fromAcc,         'debit' => 0,       'credit' => $amount, 'memo' => 'صرف خزنة'];

            return [$lines, $desc, $source];
        }

        throw new \InvalidArgumentException("Unknown delivery type: {$type}");
    }

    /**
     * ✅ Sequential entry_number: JE-YYYY-000001 per user
     * ملاحظة: لو تحب per owner per year فقط.
     */
    private function nextEntryNumberLocked(int $ownerId): string
    {
        $year = now()->format('Y');

        // Lock rows for this owner+year (simple way)
        $last = JournalEntry::query()
            ->where('user_id', $ownerId)
            ->where('entry_number', 'like', "JE-{$year}-%")
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->value('entry_number');

        $nextSeq = 1;

        if ($last && preg_match('/JE\-'.preg_quote($year, '/').'\-(\d+)/', $last, $m)) {
            $nextSeq = ((int) $m[1]) + 1;
        }

        return sprintf('JE-%s-%06d', $year, $nextSeq);
    }

    private function sumDebit(array $lines): float
    {
        return array_reduce($lines, fn ($c, $l) => $c + (float) ($l['debit'] ?? 0), 0.0);
    }

    private function sumCredit(array $lines): float
    {
        return array_reduce($lines, fn ($c, $l) => $c + (float) ($l['credit'] ?? 0), 0.0);
    }

    private function reverseByEntryId(int $entryId, int $ownerId, string $reason): JournalEntry
    {
        // ✅ Lock original entry
        $original = JournalEntry::query()
            ->where('user_id', $ownerId)
            ->where('id', $entryId)
            ->lockForUpdate()
            ->firstOrFail();

        // ✅ Prevent double reversal
        if (($original->status ?? '') === 'reversed') {
            throw new \RuntimeException("Entry already reversed: #{$original->id}");
        }

        $origLines = JournalEntryLine::query()
            ->where('user_id', $ownerId)
            ->where('journal_entry_id', $original->id)
            ->orderBy('line_no')
            ->get();

        if ($origLines->isEmpty()) {
            throw new \RuntimeException("No lines to reverse for entry #{$original->id}");
        }

        $entryNumber = $this->nextEntryNumberLocked($ownerId);

        $reversal = JournalEntry::create([
            'user_id' => $ownerId,
            'entry_number' => $entryNumber,
            'entry_date' => now()->toDateString(),
            'source' => ($original->source ?? 'journal').'_reversal',
            'reference_type' => $original->reference_type,
            'reference_id' => $original->reference_id,
            'description' => 'عكس قيد: '.($original->entry_number ?? $original->id).' - '.$reason,
            'total_debit' => (float) $original->total_credit,
            'total_credit' => (float) $original->total_debit,
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => (int) (auth()->id() ?? 0),
            'reversed_entry_id' => $original->id,
        ]);

        $lineNo = 1;
        foreach ($origLines as $l) {
            JournalEntryLine::create([
                'user_id' => $ownerId,
                'journal_entry_id' => $reversal->id,
                'account_id' => (int) $l->account_id,
                'debit' => (float) $l->credit,
                'credit' => (float) $l->debit,
                'memo' => 'Reverse: '.($l->memo ?? ''),
                'line_no' => $lineNo++,
            ]);
        }

        $this->applyEntryToBalancesAtomic($reversal);

        // ✅ Mark original as reversed (audit)
        $original->update([
            'status' => 'reversed',
        ]);

        return $reversal;
    }

    /**
     * ✅ Atomic apply to account_balances + accounts.current_balance
     * - balance هنا Net = debit_total - credit_total (مؤقتًا)
     */
    private function applyEntryToBalancesAtomic(JournalEntry $entry): void
    {
        $ownerId = (int) $entry->user_id;

        $lines = JournalEntryLine::query()
            ->where('user_id', $ownerId)
            ->where('journal_entry_id', $entry->id)
            ->get();

        if ($lines->isEmpty()) {
            return;
        }

        $grouped = $lines->groupBy('account_id');

        foreach ($grouped as $accountId => $accLines) {
            $debit = (float) $accLines->sum('debit');
            $credit = (float) $accLines->sum('credit');

            app(AccountBalanceUpdater::class)->apply(
                userId: $ownerId,
                accountId: (int) $accountId,
                currency: 'EGP',
                branchId: null,
                debit: $debit,
                credit: $credit
            );
        }
    }
}
