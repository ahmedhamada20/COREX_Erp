<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountBalance;
use Illuminate\Support\Facades\DB;

class AccountReconciliation
{
    /**
     * Reconcile a single account
     */
    public function reconcile(int $accountId, int $tenantId): array
    {
        $account = Account::query()
            ->where('user_id', $tenantId)
            ->findOrFail($accountId);

        // Calculate balance from journal entry lines
        $lineBalance = DB::table('journal_entry_lines')
            ->where('account_id', $accountId)
            ->selectRaw('
                SUM(CAST(debit AS DECIMAL(15,2))) as total_debit,
                SUM(CAST(credit AS DECIMAL(15,2))) as total_credit
            ')
            ->first();

        // Get stored balance
        $storedBalance = AccountBalance::where('account_id', $accountId)
            ->where('user_id', $tenantId)
            ->first();

        if (! $storedBalance) {
            throw new \RuntimeException(
                "No account balance record found for account {$account->account_number}. ".
                'Please run account balance update.'
            );
        }

        // Calculate net balance based on normal side
        $normalSide = $account->type->normal_side;

        $totalDebit = (float) ($lineBalance->total_debit ?? 0);
        $totalCredit = (float) ($lineBalance->total_credit ?? 0);

        if ($normalSide === 'debit') {
            $calculatedBalance = $totalDebit - $totalCredit;
        } else {
            $calculatedBalance = $totalCredit - $totalDebit;
        }

        $calculatedBalance = round($calculatedBalance, 2);
        $storedBalanceAmount = round((float) $storedBalance->balance, 2);
        $difference = round(abs($calculatedBalance - $storedBalanceAmount), 2);
        $isReconciled = $difference < 0.01;

        return [
            'account_id' => $accountId,
            'account_number' => $account->account_number,
            'account_name' => $account->name,
            'account_type' => $account->type->name,
            'normal_side' => $normalSide,
            'calculated_balance' => $calculatedBalance,
            'stored_balance' => $storedBalanceAmount,
            'difference' => $difference,
            'is_reconciled' => $isReconciled,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
        ];
    }

    /**
     * Reconcile all accounts for a tenant
     */
    public function reconcileAll(int $tenantId): array
    {
        // Get all active accounts
        $accounts = Account::query()
            ->where('user_id', $tenantId)
            ->where('status', true)
            ->where('deleted_at', null)
            ->get();

        $results = [];
        $reconcileCount = 0;
        $mismatchCount = 0;

        foreach ($accounts as $account) {
            try {
                $result = $this->reconcile($account->id, $tenantId);
                $results[] = $result;

                if ($result['is_reconciled']) {
                    $reconcileCount++;
                } else {
                    $mismatchCount++;
                }
            } catch (\Exception $e) {
                // Skip accounts with errors
                continue;
            }
        }

        return [
            'tenant_id' => $tenantId,
            'reconciled_at' => now(),
            'total_accounts' => count($results),
            'reconciled_count' => $reconcileCount,
            'mismatch_count' => $mismatchCount,
            'results' => $results,
            'all_reconciled' => $mismatchCount === 0,
        ];
    }

    /**
     * Get reconciliation summary
     */
    public function getSummary(int $tenantId): string
    {
        $report = $this->reconcileAll($tenantId);

        $output = [];
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = 'ACCOUNT RECONCILIATION REPORT';
        $output[] = 'Date: '.$report['reconciled_at'];
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = '';

        $output[] = sprintf(
            'Total Accounts: %d | Reconciled: %d | Mismatches: %d',
            $report['total_accounts'],
            $report['reconciled_count'],
            $report['mismatch_count']
        );
        $output[] = '';

        if ($report['mismatch_count'] > 0) {
            $output[] = '⚠️  ACCOUNTS WITH DISCREPANCIES:';
            $output[] = '─────────────────────────────────────────────────────────────';

            foreach ($report['results'] as $result) {
                if (! $result['is_reconciled']) {
                    $output[] = sprintf(
                        '%s (%s): Calculated: %.2f | Stored: %.2f | Diff: %.2f',
                        $result['account_number'],
                        $result['account_name'],
                        $result['calculated_balance'],
                        $result['stored_balance'],
                        $result['difference']
                    );
                }
            }
        } else {
            $output[] = '✅ ALL ACCOUNTS RECONCILED';
        }

        $output[] = '';

        return implode("\n", $output);
    }
}
