<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class LedgerWriter
{
    public function postEntry(array $payload, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($payload, $lines) {

            /** @var JournalEntry $entry */
            $entry = JournalEntry::create($payload);

            $totalDebit = 0.0;
            $totalCredit = 0.0;
            $lineNo = 1;

            foreach ($lines as $l) {
                $accountId = (int) ($l['account_id'] ?? 0);
                if ($accountId <= 0) {
                    throw new \RuntimeException("Invalid account_id in journal line: {$accountId}");
                }

                // ✅ 4 decimals
                $debit = round((float) ($l['debit'] ?? 0), 4);
                $credit = round((float) ($l['credit'] ?? 0), 4);

                if ($debit <= 0 && $credit <= 0) {
                    continue;
                }

                $totalDebit += $debit;
                $totalCredit += $credit;

                $currency = $l['currency_code'] ?? ($payload['currency_code'] ?? 'EGP');
                $branchId = $l['branch_id'] ?? ($payload['branch_id'] ?? null);

                JournalEntryLine::create([
                    'user_id' => (int) $payload['user_id'],
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accountId,

                    'cost_center_id' => $l['cost_center_id'] ?? null,
                    'project_id' => $l['project_id'] ?? null,
                    'branch_id' => $branchId,
                    'warehouse_id' => $l['warehouse_id'] ?? null,

                    'debit' => $debit,
                    'credit' => $credit,
                    'currency_code' => $currency,
                    'fx_rate' => $l['fx_rate'] ?? null,

                    'memo' => $l['memo'] ?? null,
                    'line_no' => $lineNo++,
                ]);

                app(AccountBalanceUpdater::class)->apply(
                    userId: (int) $payload['user_id'],
                    accountId: $accountId,
                    currency: (string) $currency,
                    branchId: $branchId ? (int) $branchId : null,
                    debit: $debit,
                    credit: $credit
                );
            }

            $totalDebit = round($totalDebit, 4);
            $totalCredit = round($totalCredit, 4);

            if (abs($totalDebit - $totalCredit) > 0.0001) {
                throw new \RuntimeException("Journal is not balanced: debit={$totalDebit}, credit={$totalCredit}");
            }

            $entry->update([
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'posted_at' => $payload['posted_at'] ?? now(),
                'status' => $payload['status'] ?? 'posted',
            ]);

            return $entry;
        });
    }
}
