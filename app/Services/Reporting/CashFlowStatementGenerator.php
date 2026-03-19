<?php

namespace App\Services\Reporting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CashFlowStatementGenerator
{
    /**
     * @return array{
     *     opening_cash: float,
     *     closing_cash: float,
     *     net_change: float,
     *     sections: array<string, array{inflows: float, outflows: float, net: float, lines: Collection<int, object>}>
     * }
     */
    public function generate(int $tenantId, string $fromDate, string $toDate): array
    {
        $opening = (float) DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entry_lines.user_id', $tenantId)
            ->where('accounts.account_number', 'like', '11%')
            ->whereDate('journal_entries.entry_date', '<', $fromDate)
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit - journal_entry_lines.credit),0) as opening_cash')
            ->value('opening_cash');

        $lines = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entry_lines.user_id', $tenantId)
            ->where('accounts.account_number', 'like', '11%')
            ->whereDate('journal_entries.entry_date', '>=', $fromDate)
            ->whereDate('journal_entries.entry_date', '<=', $toDate)
            ->select(
                'journal_entries.entry_date',
                'journal_entries.entry_number',
                'journal_entries.source',
                'journal_entries.description',
                'accounts.account_number',
                'accounts.name as account_name',
                'journal_entry_lines.debit',
                'journal_entry_lines.credit'
            )
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->get();

        $sections = [
            'operating' => ['inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0, 'lines' => collect()],
            'investing' => ['inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0, 'lines' => collect()],
            'financing' => ['inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0, 'lines' => collect()],
        ];

        foreach ($lines as $line) {
            $source = (string) ($line->source ?? 'manual');
            $section = 'operating';

            if (in_array($source, ['fixed_asset_purchase', 'fixed_asset_sale', 'fixed_asset_depreciation'], true)) {
                $section = 'investing';
            } elseif (in_array($source, ['capital', 'loan', 'owner_draw'], true)) {
                $section = 'financing';
            }

            $inflow = round((float) $line->debit, 2);
            $outflow = round((float) $line->credit, 2);

            $sections[$section]['inflows'] += $inflow;
            $sections[$section]['outflows'] += $outflow;
            $sections[$section]['lines']->push($line);
        }

        foreach ($sections as $key => $section) {
            $sections[$key]['inflows'] = round((float) $section['inflows'], 2);
            $sections[$key]['outflows'] = round((float) $section['outflows'], 2);
            $sections[$key]['net'] = round((float) $section['inflows'] - (float) $section['outflows'], 2);
        }

        $netChange = round(
            $sections['operating']['net'] + $sections['investing']['net'] + $sections['financing']['net'],
            2
        );

        return [
            'opening_cash' => round($opening, 2),
            'closing_cash' => round($opening + $netChange, 2),
            'net_change' => $netChange,
            'sections' => $sections,
        ];
    }
}
