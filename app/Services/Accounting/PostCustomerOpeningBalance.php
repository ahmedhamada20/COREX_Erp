<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class PostCustomerOpeningBalance
{
    public function handle(
        int $tenantId,
        Account $customerAccount,
        float $amount,
        string $actorName,
        string $entryDate,
        array $dims = []
    ): ?JournalEntry {
        $amount = (float) $amount;
        if (abs($amount) < 0.0001) {
            return null;
        }

        return DB::transaction(function () use ($tenantId, $customerAccount, $amount, $entryDate, $dims) {

            $resolver = app(AccountResolver::class);
            $offsetAccountId = $resolver->openingBalanceOffset($tenantId);

            // amount موجب => Dr العميل / Cr الأوفست
            // amount سالب => Cr العميل / Dr الأوفست
            $debitOnCustomer = $amount > 0 ? $amount : 0.0;
            $creditOnCustomer = $amount < 0 ? abs($amount) : 0.0;

            $debitOnOffset = $creditOnCustomer;
            $creditOnOffset = $debitOnCustomer;

            $currency = $dims['currency_code'] ?? 'EGP';

            $refType = Customer::class;
            $refId = $dims['reference_id'] ?? null;
            $refLabel = $dims['reference_label'] ?? $customerAccount->name;

            $entryNumber = app(JournalEntryNumberGenerator::class)->next($tenantId, new \DateTime($entryDate));

            $payload = [
                'user_id' => $tenantId,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate,
                'source' => 'opening_balance',
                'reference_type' => $refType,
                'reference_id' => $refId,
                'description' => "قيد رصيد افتتاحي - {$refLabel}",
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => null,
                'currency_code' => $currency,
                'branch_id' => $dims['branch_id'] ?? null,
            ];

            $lines = [
                [
                    'account_id' => (int) $customerAccount->id,
                    'debit' => round($debitOnCustomer, 2),
                    'credit' => round($creditOnCustomer, 2),
                    'memo' => 'Opening balance - customer',
                    'currency_code' => $currency,
                    'fx_rate' => $dims['fx_rate'] ?? 1,
                    'branch_id' => $dims['branch_id'] ?? null,
                    'warehouse_id' => $dims['warehouse_id'] ?? null,
                    'cost_center_id' => $dims['cost_center_id'] ?? null,
                    'project_id' => $dims['project_id'] ?? null,
                ],
                [
                    'account_id' => (int) $offsetAccountId,
                    'debit' => round($debitOnOffset, 2),
                    'credit' => round($creditOnOffset, 2),
                    'memo' => 'Opening balance offset',
                    'currency_code' => $currency,
                    'fx_rate' => $dims['fx_rate'] ?? 1,
                    'branch_id' => $dims['branch_id'] ?? null,
                    'warehouse_id' => $dims['warehouse_id'] ?? null,
                    'cost_center_id' => $dims['cost_center_id'] ?? null,
                    'project_id' => $dims['project_id'] ?? null,
                ],
            ];

            return app(LedgerWriter::class)->postEntry($payload, $lines);
        });
    }
}
