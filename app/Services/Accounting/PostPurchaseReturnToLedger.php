<?php

namespace App\Services\Accounting;

use App\Models\PurchaseReturn;

class PostPurchaseReturnToLedger
{
    public function handle(int $tenantId, PurchaseReturn $return, int $actorUserId): void
    {
        if (! empty($return->journal_entry_id)) {
            return;
        }

        $return->load(['items.item', 'supplier']);

        $resolver = app(AccountResolver::class);

        $inventoryId = $resolver->inventory($tenantId);
        $inputVatId = $resolver->inputVat($tenantId);
        $purchasesExpId = $resolver->purchasesExpense($tenantId);

        // ✅ AP: supplier sub-ledger or 2100
        $apAccountId = (int) ($return->supplier?->account_id ?? 0);
        if ($apAccountId <= 0) {
            $apAccountId = $resolver->apControl($tenantId);
        }

        $tax = round((float) ($return->tax_value ?? 0), 2);
        $total = round((float) ($return->total ?? 0), 2);

        $invAmount = 0.0;
        $expAmount = 0.0;

        foreach ($return->items as $line) {
            $itemType = $line->item?->type ?? 'stock';
            $netLine = round((float) ($line->line_subtotal ?? 0), 2);

            if (in_array($itemType, ['service', 'expense'], true)) {
                $expAmount += $netLine;
            } else {
                $invAmount += $netLine;
            }
        }

        $invAmount = round($invAmount, 2);
        $expAmount = round($expAmount, 2);

        // match subtotal if exists
        $targetSubtotal = round((float) ($return->subtotal ?? ($invAmount + $expAmount)), 2);
        $sumNet = round($invAmount + $expAmount, 2);
        $diff = round($targetSubtotal - $sumNet, 2);

        if (abs($diff) > 0.01) {
            if ($invAmount > 0) {
                $invAmount = round($invAmount + $diff, 2);
            } else {
                $expAmount = round($expAmount + $diff, 2);
            }
        }

        $entryDate = $return->return_date ?: now()->toDateString();
        $entryNumber = app(JournalEntryNumberGenerator::class)->next($tenantId, new \DateTime($entryDate));

        $payload = [
            'user_id' => $tenantId,
            'entry_number' => $entryNumber,
            'entry_date' => $entryDate,
            'source' => 'purchase',
            'reference_type' => PurchaseReturn::class,
            'reference_id' => $return->id,
            'description' => "Purchase Return {$return->purchase_return_code}",
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $actorUserId,
            'currency_code' => $return->currency_code ?? 'EGP',
            'branch_id' => $return->branch_id ?? null,
        ];

        $lines = [];

        // ✅ Debit AP (reduce payable)
        $lines[] = ['account_id' => $apAccountId, 'debit' => $total, 'credit' => 0, 'memo' => 'A/P (Return)'];

        // ✅ Credit inventory/expense + reverse VAT
        if ($invAmount > 0) {
            $lines[] = ['account_id' => $inventoryId,    'debit' => 0, 'credit' => $invAmount, 'memo' => 'Inventory Reverse'];
        }
        if ($expAmount > 0) {
            $lines[] = ['account_id' => $purchasesExpId, 'debit' => 0, 'credit' => $expAmount, 'memo' => 'Purchases Expense Reverse'];
        }
        if ($tax > 0) {
            $lines[] = ['account_id' => $inputVatId,     'debit' => 0, 'credit' => $tax,       'memo' => 'VAT Input Reverse'];
        }

        $je = app(LedgerWriter::class)->postEntry($payload, $lines);

        $return->update(['journal_entry_id' => $je->id]);
    }
}
