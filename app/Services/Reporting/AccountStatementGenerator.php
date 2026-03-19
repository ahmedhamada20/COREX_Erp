<?php

namespace App\Services\Reporting;

use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class AccountStatementGenerator
{
    /**
     * @return array{lines: Collection<int, JournalEntryLine>, total_debit: float, total_credit: float, net: float}
     */
    public function generate(
        int $tenantId,
        string $fromDate,
        string $toDate,
        ?int $accountId = null,
    ): array {
        $query = JournalEntryLine::query()
            ->where('user_id', $tenantId)
            ->with(['account:id,account_number,name', 'journalEntry:id,entry_number,entry_date,description,source'])
            ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate): void {
                $q->whereDate('entry_date', '>=', $fromDate)
                    ->whereDate('entry_date', '<=', $toDate);
            })
            ->orderBy('journal_entry_id')
            ->orderBy('line_no');

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        $lines = $query->get();

        $totalDebit = round((float) $lines->sum('debit'), 4);
        $totalCredit = round((float) $lines->sum('credit'), 4);

        return [
            'lines' => $lines,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'net' => round($totalDebit - $totalCredit, 4),
        ];
    }
}

