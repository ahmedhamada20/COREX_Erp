<?php

namespace App\Services\Reporting;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

class BalanceSheetGenerator
{
    /**
     * Generate Balance Sheet as of a specific date
     *
     * @param  string  $asOfDate  Format: YYYY-MM-DD
     */
    public function generate(int $tenantId, string $asOfDate): array
    {
        // Assets (1000 series)
        $assets = $this->getAccountGroupBalance($tenantId, ['1%'], $asOfDate);

        // Liabilities (2000 series)
        $liabilities = $this->getAccountGroupBalance($tenantId, ['2%'], $asOfDate);

        // Equity (3000 series)
        $equity = $this->getAccountGroupBalance($tenantId, ['3%'], $asOfDate);

        $totalAssets = $assets['total'];
        $totalLiabilities = $liabilities['total'];
        $totalEquity = $equity['total'];

        // Check equation: Assets = Liabilities + Equity
        $difference = abs($totalAssets - ($totalLiabilities + $totalEquity));
        $isBalanced = $difference < 0.01;

        return [
            'as_of_date' => $asOfDate,
            'generated_at' => now(),
            'assets' => [
                'accounts' => $assets['accounts'],
                'total' => round($totalAssets, 2),
            ],
            'liabilities' => [
                'accounts' => $liabilities['accounts'],
                'total' => round($totalLiabilities, 2),
            ],
            'equity' => [
                'accounts' => $equity['accounts'],
                'total' => round($totalEquity, 2),
            ],
            'liabilities_and_equity' => round($totalLiabilities + $totalEquity, 2),
            'equation_balanced' => $isBalanced,
            'difference' => round($difference, 2),
        ];
    }

    /**
     * Get balance for an account group
     */
    private function getAccountGroupBalance(
        int $tenantId,
        array $accountPatterns,
        string $asOfDate
    ): array {
        $query = DB::table('account_balances')
            ->join('accounts', 'account_balances.account_id', '=', 'accounts.id')
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->where('account_balances.user_id', $tenantId)
            ->where('accounts.deleted_at', null)
            ->select(
                'accounts.id',
                'accounts.account_number',
                'accounts.name',
                'account_types.normal_side',
                'account_balances.balance'
            );

        // Apply account patterns
        $query->where(function ($q) use ($accountPatterns) {
            foreach ($accountPatterns as $pattern) {
                $q->orWhere('accounts.account_number', 'like', $pattern);
            }
        });

        $results = $query->orderBy('accounts.account_number')->get();

        $accounts = [];
        $total = 0.0;

        foreach ($results as $row) {
            $balance = (float) ($row->balance ?? 0);

            // Only show accounts with non-zero balances
            if (abs($balance) < 0.01) {
                continue;
            }

            $total += $balance;

            $accounts[] = [
                'account_number' => $row->account_number,
                'account_name' => $row->name,
                'normal_side' => $row->normal_side,
                'balance' => round($balance, 2),
            ];
        }

        return [
            'accounts' => $accounts,
            'total' => round($total, 2),
        ];
    }

    /**
     * Format Balance Sheet for display
     */
    public function formatForDisplay(array $balanceSheet): string
    {
        $output = [];
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = 'BALANCE SHEET';
        $output[] = 'As of: '.$balanceSheet['as_of_date'];
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = '';

        // Assets
        $output[] = 'ASSETS';
        $output[] = '─────────────────────────────────────────────────────────────';
        foreach ($balanceSheet['assets']['accounts'] as $acc) {
            $output[] = sprintf(
                '  %-40s %15.2f',
                substr($acc['account_name'], 0, 40),
                $acc['balance']
            );
        }
        $output[] = sprintf(
            '%-40s %15.2f',
            'Total Assets',
            $balanceSheet['assets']['total']
        );
        $output[] = '';

        // Liabilities
        $output[] = 'LIABILITIES';
        $output[] = '─────────────────────────────────────────────────────────────';
        foreach ($balanceSheet['liabilities']['accounts'] as $acc) {
            $output[] = sprintf(
                '  %-40s %15.2f',
                substr($acc['account_name'], 0, 40),
                $acc['balance']
            );
        }
        $output[] = sprintf(
            '%-40s %15.2f',
            'Total Liabilities',
            $balanceSheet['liabilities']['total']
        );
        $output[] = '';

        // Equity
        $output[] = 'EQUITY';
        $output[] = '─────────────────────────────────────────────────────────────';
        foreach ($balanceSheet['equity']['accounts'] as $acc) {
            $output[] = sprintf(
                '  %-40s %15.2f',
                substr($acc['account_name'], 0, 40),
                $acc['balance']
            );
        }
        $output[] = sprintf(
            '%-40s %15.2f',
            'Total Equity',
            $balanceSheet['equity']['total']
        );
        $output[] = '';

        // Liabilities + Equity
        $output[] = sprintf(
            '%-40s %15.2f',
            'Total Liabilities + Equity',
            $balanceSheet['liabilities_and_equity']
        );
        $output[] = '';

        // Balance Check
        $output[] = '═══════════════════════════════════════════════════════════════';
        if ($balanceSheet['equation_balanced']) {
            $output[] = '✅ EQUATION BALANCED';
            $output[] = sprintf(
                'Assets (%.2f) = Liabilities (%.2f) + Equity (%.2f)',
                $balanceSheet['assets']['total'],
                $balanceSheet['liabilities']['total'],
                $balanceSheet['equity']['total']
            );
        } else {
            $output[] = '❌ EQUATION NOT BALANCED';
            $output[] = sprintf(
                'Difference: %.2f',
                $balanceSheet['difference']
            );
        }
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = '';

        return implode("\n", $output);
    }
}
