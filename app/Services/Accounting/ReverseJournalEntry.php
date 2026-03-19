<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class ReverseJournalEntry
{
    public function handle(int $tenantId, int $entryId, int $actorUserId, ?string $reason = null): JournalEntry
    {
        return DB::transaction(function () use ($tenantId, $entryId, $actorUserId, $reason) {

            /** @var JournalEntry $entry */
            $entry = JournalEntry::query()
                ->with('lines')
                ->where('user_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($entryId);

            // ✅ Prevent reversing already reversed entries
            if (($entry->status ?? '') === 'reversed') {
                throw new \RuntimeException(
                    "Journal entry {$entry->entry_number} has already been reversed. ".
                    'Cannot reverse it again. If you need to correct this, please reverse the reversal entry instead.'
                );
            }

            // ✅ Prevent reversing entries that are reversals themselves
            if (! empty($entry->reversed_entry_id)) {
                throw new \RuntimeException(
                    "Entry {$entry->entry_number} is itself a reversal entry. ".
                    'Cannot reverse a reversal. Please contact your administrator.'
                );
            }

            if ($entry->lines->isEmpty()) {
                throw new \RuntimeException('Journal entry has no lines to reverse.');
            }

            // ✅ New entry number (same entry_date)
            $entryNumber = app(JournalEntryNumberGenerator::class)->next(
                $tenantId,
                new \DateTime($entry->entry_date)
            );

            $desc = trim(
                'Reversal: '.($entry->description ?? '').($reason ? " | {$reason}" : '')
            );

            /** @var JournalEntry $rev */
            $rev = JournalEntry::create([
                'user_id' => $tenantId,
                'entry_number' => $entryNumber,
                'entry_date' => $entry->entry_date,
                'source' => ($entry->source ?? 'journal').'_reversal',
                'reference_type' => $entry->reference_type,
                'reference_id' => $entry->reference_id,
                'description' => $desc,
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actorUserId,

                // ✅ link reversal -> original (you have reversed_entry_id column)
                'reversed_entry_id' => $entry->id,
            ]);

            $totalDebit = 0.0;
            $totalCredit = 0.0;

            $lineNo = 1;

            foreach ($entry->lines as $line) {
                $origDebit = round((float) ($line->debit ?? 0), 2);
                $origCredit = round((float) ($line->credit ?? 0), 2);

                if ($origDebit <= 0 && $origCredit <= 0) {
                    continue;
                }

                // ✅ swap
                $newDebit = $origCredit;
                $newCredit = $origDebit;

                $newDebit = round($newDebit, 2);
                $newCredit = round($newCredit, 2);

                $totalDebit += $newDebit;
                $totalCredit += $newCredit;

                $currency = (string) ($line->currency_code ?: 'EGP');
                $branchId = $line->branch_id ? (int) $line->branch_id : null;

                JournalEntryLine::create([
                    'user_id' => $tenantId,
                    'journal_entry_id' => $rev->id,
                    'account_id' => (int) $line->account_id,

                    'cost_center_id' => $line->cost_center_id,
                    'project_id' => $line->project_id,
                    'branch_id' => $branchId,
                    'warehouse_id' => $line->warehouse_id,

                    'debit' => $newDebit,
                    'credit' => $newCredit,

                    'currency_code' => $currency,
                    'fx_rate' => $line->fx_rate,

                    'memo' => 'Reversal: '.($line->memo ?? ''),
                    'line_no' => $lineNo++,
                ]);

                // ✅ Update account_balances exactly per schema (موحد)
                app(AccountBalanceUpdater::class)->apply(
                    userId: $tenantId,
                    accountId: (int) $line->account_id,
                    currency: $currency,
                    branchId: $branchId,
                    debit: $newDebit,
                    credit: $newCredit
                );
            }

            $totalDebit = round($totalDebit, 2);
            $totalCredit = round($totalCredit, 2);

            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw new \RuntimeException("Reversal journal not balanced: debit={$totalDebit}, credit={$totalCredit}");
            }

            $rev->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            // ✅ mark original as reversed + link original -> reversal
            $entry->update([
                'status' => 'reversed',
                'reversed_entry_id' => $rev->id,
            ]);

            return $rev;
        });
    }
}
