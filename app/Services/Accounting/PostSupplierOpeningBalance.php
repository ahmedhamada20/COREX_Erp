<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PostSupplierOpeningBalance
{
    public function handle(
        int $tenantId,
        Account $supplierAccount,
        float $amount,           // + vendor credit (we owe) / - vendor debit (vendor owes)
        string $actorName,
        string $entryDate,
        array $dims = []
    ): ?JournalEntry {
        $amount = (float) $amount;
        if (abs($amount) < 0.0001) {
            return null;
        }

        return DB::transaction(function () use ($tenantId, $supplierAccount, $amount, $entryDate, $dims) {

            $resolver = app(AccountResolver::class);
            $offsetAccountId = $resolver->openingBalanceOffset($tenantId);

            // ✅ A/P logic:
            // amount > 0  => إحنا مدينين للمورد => Cr المورد / Dr الأوفست
            // amount < 0  => المورد مدين لنا     => Dr المورد / Cr الأوفست
            $creditOnSupplier = $amount > 0 ? $amount : 0.0;
            $debitOnSupplier = $amount < 0 ? abs($amount) : 0.0;

            $debitOnOffset = $creditOnSupplier;
            $creditOnOffset = $debitOnSupplier;

            $currency = $dims['currency_code'] ?? 'EGP';

            $refType = Supplier::class;
            $refId = $dims['reference_id'] ?? null;
            $refLabel = $dims['reference_label'] ?? $supplierAccount->name;

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
                    'account_id' => (int) $supplierAccount->id,
                    'debit' => round($debitOnSupplier, 2),
                    'credit' => round($creditOnSupplier, 2),
                    'memo' => 'Opening balance - supplier',
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
