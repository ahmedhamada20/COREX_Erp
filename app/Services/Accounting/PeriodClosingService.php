<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class PeriodClosingService
{
    /**
     * Close an accounting period
     *
     * @param  string  $periodEndDate  Format: YYYY-MM-DD
     */
    public function close(int $tenantId, string $periodEndDate, int $closedByUserId): array
    {
        return DB::transaction(function () use ($tenantId, $periodEndDate, $closedByUserId) {

            // 1️⃣ Check for unposted journal entries
            $unpostedCount = JournalEntry::query()
                ->where('user_id', $tenantId)
                ->where('entry_date', '<=', $periodEndDate)
                ->where('status', '!=', 'posted')
                ->count();

            if ($unpostedCount > 0) {
                throw new \RuntimeException(
                    "Cannot close period: {$unpostedCount} unposted journal entries exist. ".
                    'Please post all entries before closing the period.'
                );
            }

            // 2️⃣ Generate and validate Trial Balance
            $trialBalance = app(TrialBalanceGenerator::class)->generate($tenantId, $periodEndDate);

            if (! $trialBalance['is_balanced']) {
                throw new \RuntimeException(
                    'Cannot close period: Trial Balance is not balanced. '.
                    'Difference: '.$trialBalance['difference'].'. '.
                    'Please reconcile all accounts first.'
                );
            }

            // 3️⃣ Calculate Net Income
            $netIncome = $this->calculateNetIncome($tenantId, $periodEndDate);

            // 4️⃣ Create closing journal entry (if net income is not zero)
            $closingEntryId = null;
            if (abs($netIncome) > 0.01) {
                $closingEntryId = $this->createClosingEntry($tenantId, $periodEndDate, $netIncome, $closedByUserId);
            }

            // 5️⃣ Record period closure
            $closureName = 'Period Closure - '.$periodEndDate;
            $closure = DB::table('period_closures')->insert([
                'user_id' => $tenantId,
                'period_end_date' => $periodEndDate,
                'net_income' => $netIncome,
                'closing_entry_id' => $closingEntryId,
                'closed_at' => now(),
                'closed_by' => $closedByUserId,
                'status' => 'closed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'status' => 'success',
                'period_end_date' => $periodEndDate,
                'net_income' => round($netIncome, 2),
                'closing_entry_id' => $closingEntryId,
                'unposted_entries' => 0,
                'trial_balance_balanced' => true,
                'closed_at' => now(),
            ];
        });
    }

    /**
     * Calculate net income for the period
     */
    private function calculateNetIncome(int $tenantId, string $periodEndDate): float
    {
        // Get all revenue accounts (4000 series)
        $revenues = DB::table('journal_entry_lines')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.user_id', $tenantId)
            ->where('journal_entries.entry_date', '<=', $periodEndDate)
            ->where('journal_entries.status', 'posted')
            ->where(function ($q) {
                // Revenue accounts are typically Credit normal side
                $q->where('accounts.account_number', 'like', '4%');
            })
            ->sum('journal_entry_lines.credit');

        // Get all expense accounts (5000 series)
        $expenses = DB::table('journal_entry_lines')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.user_id', $tenantId)
            ->where('journal_entries.entry_date', '<=', $periodEndDate)
            ->where('journal_entries.status', 'posted')
            ->where(function ($q) {
                // Expense accounts are typically Debit normal side
                $q->where('accounts.account_number', 'like', '5%');
            })
            ->sum('journal_entry_lines.debit');

        $revenues = (float) ($revenues ?? 0);
        $expenses = (float) ($expenses ?? 0);

        return round($revenues - $expenses, 2);
    }

    /**
     * Create closing journal entry
     */
    private function createClosingEntry(int $tenantId, string $periodEndDate, float $netIncome, int $actorUserId): ?int
    {
        $resolver = app(AccountResolver::class);

        // Closing accounts (typically 8000 series or special equity accounts)
        $incomeAccountId = $resolver->incomeRetainedEarnings($tenantId) ??
                          \App\Models\Account::where('user_id', $tenantId)
                              ->where('account_number', 'like', '39%')
                              ->first()?->id;

        if (! $incomeAccountId) {
            // If no closing account exists, skip
            return null;
        }

        $entryNumber = app(JournalEntryNumberGenerator::class)->next($tenantId, new \DateTime($periodEndDate));

        $payload = [
            'user_id' => $tenantId,
            'entry_number' => $entryNumber,
            'entry_date' => $periodEndDate,
            'source' => 'period_closing',
            'description' => "Period Closing Entry - {$periodEndDate}",
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $actorUserId,
            'currency_code' => 'EGP',
        ];

        $lines = [];

        if ($netIncome > 0.01) {
            // Profit: Debit Retained Earnings, Credit Income Summary
            $lines[] = [
                'account_id' => $incomeAccountId,
                'debit' => round($netIncome, 2),
                'credit' => 0,
                'memo' => 'Retained Earnings - Net Income',
            ];
        } else {
            // Loss: Credit Retained Earnings, Debit Income Summary
            $lines[] = [
                'account_id' => $incomeAccountId,
                'debit' => 0,
                'credit' => round(abs($netIncome), 2),
                'memo' => 'Retained Earnings - Net Loss',
            ];
        }

        $je = app(LedgerWriter::class)->postEntry($payload, $lines);

        return $je->id;
    }

    /**
     * Get closure status
     */
    public function isPeriodClosed(int $tenantId, string $periodEndDate): bool
    {
        return DB::table('period_closures')
            ->where('user_id', $tenantId)
            ->where('period_end_date', $periodEndDate)
            ->where('status', 'closed')
            ->exists();
    }
}
