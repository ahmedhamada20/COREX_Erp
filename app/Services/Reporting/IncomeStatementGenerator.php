<?php

namespace App\Services\Reporting;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

class IncomeStatementGenerator
{
    /**
     * Generate Income Statement for a period
     *
     * @param  string  $startDate  Format: YYYY-MM-DD
     * @param  string  $endDate  Format: YYYY-MM-DD
     */
    public function generate(int $tenantId, string $startDate, string $endDate): array
    {
        // Get revenues (4000 series, Credit normal)
        $revenues = $this->getAccountGroupSummary($tenantId, ['4%'], $startDate, $endDate);

        // Get cost of goods sold (5100, Debit normal)
        $cogs = $this->getAccountGroupSummary($tenantId, ['51%'], $startDate, $endDate);

        // Get operating expenses (5200-5900, Debit normal)
        $expenses = $this->getAccountGroupSummary($tenantId, ['52%', '53%', '54%', '55%', '56%'], $startDate, $endDate);

        // Calculate gross profit
        $totalRevenue = $revenues['total'];
        $totalCogs = $cogs['total'];
        $grossProfit = $totalRevenue - $totalCogs;

        // Calculate operating income
        $totalExpenses = $expenses['total'];
        $operatingIncome = $grossProfit - $totalExpenses;

        // Get other income/expense (6000-6900, Debit/Credit)
        $otherIncome = $this->getAccountGroupSummary($tenantId, ['61%', '62%'], $startDate, $endDate);
        $otherExpense = $this->getAccountGroupSummary($tenantId, ['68%', '69%'], $startDate, $endDate);

        $netOtherIncome = $otherIncome['total'] - $otherExpense['total'];

        // Calculate net income
        $netIncome = $operatingIncome + $netOtherIncome;

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'generated_at' => now(),
            'revenues' => [
                'accounts' => $revenues['accounts'],
                'total' => round($totalRevenue, 2),
            ],
            'cost_of_goods_sold' => [
                'accounts' => $cogs['accounts'],
                'total' => round($totalCogs, 2),
            ],
            'gross_profit' => round($grossProfit, 2),
            'operating_expenses' => [
                'accounts' => $expenses['accounts'],
                'total' => round($totalExpenses, 2),
            ],
            'operating_income' => round($operatingIncome, 2),
            'other_income' => [
                'accounts' => $otherIncome['accounts'],
                'total' => round($otherIncome['total'], 2),
            ],
            'other_expenses' => [
                'accounts' => $otherExpense['accounts'],
                'total' => round($otherExpense['total'], 2),
            ],
            'net_income' => round($netIncome, 2),
        ];
    }

    /**
     * Get summary of an account group
     */
    private function getAccountGroupSummary(
        int $tenantId,
        array $accountPatterns,
        string $startDate,
        string $endDate
    ): array {
        $query = DB::table('journal_entry_lines')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.user_id', $tenantId)
            ->where('journal_entries.entry_date', '>=', $startDate)
            ->where('journal_entries.entry_date', '<=', $endDate)
            ->where('journal_entries.status', 'posted')
            ->select(
                'accounts.id',
                'accounts.account_number',
                'accounts.name',
                DB::raw('SUM(CAST(journal_entry_lines.debit AS DECIMAL(15,2))) as total_debit'),
                DB::raw('SUM(CAST(journal_entry_lines.credit AS DECIMAL(15,2))) as total_credit')
            )
            ->groupBy('accounts.id', 'accounts.account_number', 'accounts.name');

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
            $debit = (float) ($row->total_debit ?? 0);
            $credit = (float) ($row->total_credit ?? 0);

            // For income statement, use the appropriate amount based on account type
            $amount = max($debit, $credit);
            $total += $amount;

            $accounts[] = [
                'account_number' => $row->account_number,
                'account_name' => $row->name,
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'amount' => round($amount, 2),
            ];
        }

        return [
            'accounts' => $accounts,
            'total' => round($total, 2),
        ];
    }

    /**
     * Format Income Statement for display
     */
    public function formatForDisplay(array $statement): string
    {
        $output = [];
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = 'INCOME STATEMENT';
        $output[] = sprintf(
            'For the Period: %s to %s',
            $statement['period_start'],
            $statement['period_end']
        );
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = '';

        // Revenues
        $output[] = 'REVENUES';
        $output[] = '─────────────────────────────────────────────────────────────';
        foreach ($statement['revenues']['accounts'] as $acc) {
            $output[] = sprintf(
                '  %-40s %15.2f',
                substr($acc['account_name'], 0, 40),
                $acc['amount']
            );
        }
        $output[] = sprintf(
            '%-40s %15.2f',
            'Total Revenues',
            $statement['revenues']['total']
        );
        $output[] = '';

        // COGS
        $output[] = 'COST OF GOODS SOLD';
        $output[] = '─────────────────────────────────────────────────────────────';
        foreach ($statement['cost_of_goods_sold']['accounts'] as $acc) {
            $output[] = sprintf(
                '  %-40s %15.2f',
                substr($acc['account_name'], 0, 40),
                $acc['amount']
            );
        }
        $output[] = sprintf(
            '%-40s %15.2f',
            'Total COGS',
            $statement['cost_of_goods_sold']['total']
        );
        $output[] = '';

        // Gross Profit
        $output[] = sprintf(
            '%-40s %15.2f',
            'GROSS PROFIT',
            $statement['gross_profit']
        );
        $output[] = '';

        // Operating Expenses
        $output[] = 'OPERATING EXPENSES';
        $output[] = '─────────────────────────────────────────────────────────────';
        foreach ($statement['operating_expenses']['accounts'] as $acc) {
            $output[] = sprintf(
                '  %-40s %15.2f',
                substr($acc['account_name'], 0, 40),
                $acc['amount']
            );
        }
        $output[] = sprintf(
            '%-40s %15.2f',
            'Total Operating Expenses',
            $statement['operating_expenses']['total']
        );
        $output[] = '';

        // Operating Income
        $output[] = sprintf(
            '%-40s %15.2f',
            'OPERATING INCOME',
            $statement['operating_income']
        );
        $output[] = '';

        // Other Income/Expenses
        $output[] = 'OTHER INCOME (EXPENSE)';
        $output[] = '─────────────────────────────────────────────────────────────';
        $output[] = sprintf(
            '  %-40s %15.2f',
            'Other Income',
            $statement['other_income']['total']
        );
        $output[] = sprintf(
            '  %-40s %15.2f',
            'Other Expenses',
            -$statement['other_expenses']['total']
        );
        $output[] = '';

        // Net Income
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = sprintf(
            '%-40s %15.2f',
            'NET INCOME (LOSS)',
            $statement['net_income']
        );
        $output[] = '═══════════════════════════════════════════════════════════════';
        $output[] = '';

        return implode("\n", $output);
    }
}
