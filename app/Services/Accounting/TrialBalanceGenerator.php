<?php

namespace App\Services\Accounting;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

class TrialBalanceGenerator
{
    /**
     * Generate a Trial Balance report for a specific date
     *
     * @param  string|null  $asOfDate  Format: YYYY-MM-DD
     */
    public function generate(int $tenantId, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();

        // Get all account balances as of the specified date
        $balances = DB::table('account_balances')
            ->join('accounts', 'account_balances.account_id', '=', 'accounts.id')
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->where('account_balances.user_id', $tenantId)
            ->where('accounts.deleted_at', null)
            ->select(
                'accounts.id',
                'accounts.account_number',
                'accounts.name',
                'account_types.name as type_name',
                'account_types.normal_side',
                'account_balances.debit_total',
                'account_balances.credit_total',
                'account_balances.balance'
            )
            ->orderBy('accounts.account_number')
            ->get();

        $totalDebits = 0.0;
        $totalCredits = 0.0;
        $reportLines = [];

        foreach ($balances as $bal) {
            // Only include accounts with non-zero balances
            if (abs((float) $bal->balance) < 0.01) {
                continue;
            }

            $debitAmount = 0.0;
            $creditAmount = 0.0;

            // Calculate debit and credit based on normal side
            if ($bal->normal_side === 'debit') {
                $debitAmount = max(0, (float) $bal->balance);
                $creditAmount = max(0, -((float) $bal->balance));
            } else {
                $creditAmount = max(0, (float) $bal->balance);
                $debitAmount = max(0, -((float) $bal->balance));
            }

            $totalDebits += $debitAmount;
            $totalCredits += $creditAmount;

            $reportLines[] = [
                'account_id' => $bal->id,
                'account_number' => $bal->account_number,
                'account_name' => $bal->name,
                'account_type' => $bal->type_name,
                'normal_side' => $bal->normal_side,
                'debit' => round($debitAmount, 2),
                'credit' => round($creditAmount, 2),
                'balance' => round((float) $bal->balance, 2),
            ];
        }

        $totalDebits = round($totalDebits, 2);
        $totalCredits = round($totalCredits, 2);
        $difference = round($totalDebits - $totalCredits, 2);
        $isBalanced = abs($difference) < 0.01;

        return [
            'as_of_date' => $asOfDate,
            'generated_at' => now(),
            'tenant_id' => $tenantId,
            'lines' => $reportLines,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'difference' => $difference,
            'is_balanced' => $isBalanced,
            'account_count' => count($reportLines),
        ];
    }

    /**
     * Format the trial balance for display
     */
    public function formatForDisplay(array $trialBalance): string
    {
        $output = [];
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = 'TRIAL BALANCE REPORT';
        $output[] = 'As of: '.$trialBalance['as_of_date'];
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = '';

        // Header
        $output[] = sprintf(
            '%-10s %-30s %-15s %15s %15s',
            'Account #',
            'Account Name',
            'Type',
            'Debit',
            'Credit'
        );
        $output[] = '─────────────────────────────────────────────────────────────────';

        // Lines
        foreach ($trialBalance['lines'] as $line) {
            $output[] = sprintf(
                '%-10s %-30s %-15s %15.2f %15.2f',
                $line['account_number'],
                substr($line['account_name'], 0, 30),
                substr($line['account_type'], 0, 15),
                $line['debit'],
                $line['credit']
            );
        }

        // Totals
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = sprintf(
            '%-10s %-30s %-15s %15.2f %15.2f',
            '',
            'TOTALS',
            '',
            $trialBalance['total_debits'],
            $trialBalance['total_credits']
        );
        $output[] = '═══════════════════════════════════════════════════════════════';

        if ($trialBalance['is_balanced']) {
            $output[] = '✅ BALANCED - Debits = Credits';
        } else {
            $output[] = '❌ NOT BALANCED - Difference: '.$trialBalance['difference'];
        }

        $output[] = '';

        return implode("\n", $output);
    }
}
