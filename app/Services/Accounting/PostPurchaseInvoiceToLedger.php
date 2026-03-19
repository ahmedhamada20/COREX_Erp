<?php

namespace App\Services\Accounting;

use App\Models\PurchaseInvoice;
use App\Services\Inventory\StockMovementRecorder;

class PostPurchaseInvoiceToLedger
{
    public function handle(int $tenantId, PurchaseInvoice $invoice, int $actorUserId): void
    {
        if (! empty($invoice->journal_entry_id)) {
            return;
        }

        $invoice->load(['items.item', 'supplier']);

        $resolver = app(AccountResolver::class);

        $cashAccountId = $resolver->cash($tenantId);
        $inventoryId = $resolver->inventory($tenantId);
        $inputVatId = $resolver->inputVat($tenantId);
        $shippingExpId = $resolver->shippingPurchases($tenantId);
        $otherExpId = $resolver->purchaseCharges($tenantId);
        $purchasesExpId = $resolver->purchasesExpense($tenantId);

        // ✅ AP: supplier sub-ledger or control 2100
        $apAccountId = (int) ($invoice->supplier?->account_id ?? 0);

        if ($apAccountId <= 0) {
            $apAccountId = $resolver->apControl($tenantId);
        } else {
            // ✅ Strict validation: ensure account exists
            $supplierAcc = \App\Models\Account::query()
                ->where('user_id', $tenantId)
                ->findOrFail($apAccountId);

            if (! $supplierAcc->type->allow_posting) {
                throw new \RuntimeException(
                    "Supplier account '{$supplierAcc->name}' ({$supplierAcc->account_number}) ".
                    "does not allow posting. Please use a different account for supplier '{$invoice->supplier?->name}'."
                );
            }
        }

        $tax = round((float) ($invoice->tax_value ?? 0), 2);
        $shipping = round((float) ($invoice->shipping_cost ?? 0), 2);
        $other = round((float) ($invoice->other_charges ?? 0), 2);
        $total = round((float) ($invoice->total ?? 0), 2);

        // net per line after line discount
        $invAmount = 0.0; // stock items -> inventory
        $expAmount = 0.0; // service/expense items -> purchases expense

        foreach ($invoice->items as $line) {
            $itemType = $line->item?->type ?? 'stock';
            $lineSubtotal = (float) ($line->line_subtotal ?? 0);
            $lineDiscount = (float) ($line->discount_value ?? 0);

            $netLine = max(0, $lineSubtotal - $lineDiscount);

            if (in_array($itemType, ['service', 'expense'], true)) {
                $expAmount += $netLine;
            } else {
                $invAmount += $netLine;
            }
        }

        $invAmount = round($invAmount, 2);
        $expAmount = round($expAmount, 2);

        // ✅ Force sum == invoice subtotal (after all discounts)
        $targetSubtotal = round((float) ($invoice->subtotal ?? 0), 2);
        $sumNet = round($invAmount + $expAmount, 2);
        $diff = round($targetSubtotal - $sumNet, 2);

        if (abs($diff) > 0.01) {
            if ($invAmount > 0) {
                $invAmount = round($invAmount + $diff, 2);
            } else {
                $expAmount = round($expAmount + $diff, 2);
            }
        }

        $creditAccount = (($invoice->payment_type ?? 'credit') === 'cash') ? $cashAccountId : $apAccountId;

        $entryDate = $invoice->invoice_date ?: now()->toDateString();
        $entryNumber = app(JournalEntryNumberGenerator::class)->next($tenantId, new \DateTime($entryDate));

        $payload = [
            'user_id' => $tenantId,
            'entry_number' => $entryNumber,
            'entry_date' => $entryDate,
            'source' => 'purchase',
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $invoice->id,
            'description' => "Purchase Invoice {$invoice->purchase_invoice_code}",
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $actorUserId,
            'currency_code' => $invoice->currency_code ?? 'EGP',
            'branch_id' => $invoice->branch_id ?? null,
        ];

        $lines = [];

        if ($invAmount > 0) {
            $lines[] = ['account_id' => $inventoryId,    'debit' => $invAmount, 'credit' => 0, 'memo' => 'Inventory / Purchases'];
        }
        if ($expAmount > 0) {
            $lines[] = ['account_id' => $purchasesExpId, 'debit' => $expAmount, 'credit' => 0, 'memo' => 'Purchases Expense'];
        }
        if ($tax > 0) {
            $lines[] = ['account_id' => $inputVatId,     'debit' => $tax,       'credit' => 0, 'memo' => 'VAT Input'];
        }
        if ($shipping > 0) {
            $lines[] = ['account_id' => $shippingExpId,  'debit' => $shipping,  'credit' => 0, 'memo' => 'Purchase Freight'];
        }
        if ($other > 0) {
            $lines[] = ['account_id' => $otherExpId,     'debit' => $other,     'credit' => 0, 'memo' => 'Other Purchase Charges'];
        }

        // credit side
        $lines[] = [
            'account_id' => $creditAccount,
            'debit' => 0,
            'credit' => $total,
            'memo' => (($invoice->payment_type ?? 'credit') === 'cash') ? 'Cash' : 'A/P',
        ];

        $je = app(LedgerWriter::class)->postEntry($payload, $lines);

        $invoice->update(['journal_entry_id' => $je->id]);

        // ✅ تسجيل حركات المخزون (purchase_in) لكل صنف في الفاتورة
        $recorder = app(StockMovementRecorder::class);
        foreach ($invoice->items as $line) {
            $type = $line->item?->type ?? 'store';
            if (in_array($type, ['service', 'expense'], true)) {
                continue;
            }

            $recorder->record([
                'user_id' => $tenantId,
                'item_id' => $line->item_id,
                'store_id' => $line->store_id ?? null,
                'movement_type' => 'purchase_in',
                'quantity' => $line->qty ?? $line->quantity ?? 0,
                'unit_cost' => $line->unit_price ?? $line->cost_price ?? 0,
                'reference_type' => PurchaseInvoice::class,
                'reference_id' => $invoice->id,
                'notes' => "Purchase Invoice {$invoice->purchase_invoice_code}",
            ]);
        }
    }
}
