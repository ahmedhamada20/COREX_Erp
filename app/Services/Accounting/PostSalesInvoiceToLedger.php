<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\SalesInvoice;
use App\Services\Inventory\StockMovementRecorder;

class PostSalesInvoiceToLedger
{
    public function handle(int $tenantId, SalesInvoice $invoice, int $actorUserId): void
    {
        if (! empty($invoice->journal_entry_id)) {
            return;
        }

        $invoice->load(['items.item', 'customer', 'payments']);

        $resolver = app(AccountResolver::class);

        // Accounts
        $cashAccountId = $resolver->cash($tenantId);
        $cardAccountId = $resolver->cardClearing($tenantId);
        $walletAccountId = $resolver->walletClearing($tenantId);

        $arControlId = $resolver->arControl($tenantId);
        $salesRevenueId = $resolver->salesRevenue($tenantId);
        $outputVatId = $resolver->outputVat($tenantId);

        $cogsId = $resolver->cogs($tenantId);
        $inventoryId = $resolver->inventory($tenantId);

        $salesDiscountId = $resolver->salesDiscount($tenantId);

        // Customer sub-ledger fallback
        $arAccountId = $arControlId;
        $customerAccNo = $invoice->customer?->account_number;

        if (! empty($customerAccNo)) {
            $acc = Account::query()
                ->where('user_id', $tenantId)
                ->where('account_number', $customerAccNo)
                ->first();

            // ✅ Strict validation: throw error if customer account not found
            if (! $acc) {
                throw new \RuntimeException(
                    "Customer account '{$customerAccNo}' not found for customer '{$invoice->customer?->name}'. ".
                    'Please create the account first or update the customer record.'
                );
            }

            $arAccountId = (int) $acc->id;
        }

        // ========= recompute netBeforeInvDiscount from lines =========
        $netBeforeInvDisc = 0.0;

        foreach ($invoice->items as $line) {
            $qty = (float) ($line->quantity ?? 0);
            $price = (float) ($line->price ?? 0);
            $disc = (float) ($line->discount ?? 0);
            $netBeforeInvDisc += max(0, ($qty * $price) - $disc);
        }

        $netBeforeInvDisc = round($netBeforeInvDisc, 4);

        $discount = round((float) ($invoice->discount_amount ?? 0), 4);
        $discount = min($discount, $netBeforeInvDisc);

        // ✅ after recalcInvoice fix: subtotal = tax base after invoice discount, vat_amount correct
        $vat = round((float) ($invoice->vat_amount ?? 0), 4);
        $total = round((float) ($invoice->total ?? 0), 4);

        if ($total <= 0.0001) {
            throw new \RuntimeException("Invalid sales invoice total for invoice {$invoice->id}");
        }

        // ========= COGS (stock only) =========
        $cogs = 0.0;
        foreach ($invoice->items as $line) {
            $type = $line->item?->type ?? 'stock';
            if (in_array($type, ['service', 'expense'], true)) {
                continue;
            }

            $qty = (float) ($line->quantity ?? 0);
            $cost = (float) ($line->cost_price ?? 0);

            // ✅ Validation: Ensure cost_price exists for stock items
            if ($cost <= 0) {
                throw new \RuntimeException(
                    "Item '{$line->item?->name}' (ID: {$line->item_id}) has no cost price. ".
                    "Cannot calculate COGS for sales invoice {$invoice->invoice_code}. ".
                    'Please set the cost price for this item first.'
                );
            }

            $cogs += ($qty * $cost);
        }
        $cogs = round($cogs, 4);

        $entryDate = $invoice->invoice_date ?: now()->toDateString();
        $entryNumber = app(JournalEntryNumberGenerator::class)->next($tenantId, new \DateTime($entryDate));

        $payload = [
            'user_id' => $tenantId,
            'entry_number' => $entryNumber,
            'entry_date' => $entryDate,
            'source' => 'sales',
            'reference_type' => SalesInvoice::class,
            'reference_id' => $invoice->id,
            'description' => "Sales Invoice {$invoice->invoice_code}",
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $actorUserId,
            'currency_code' => 'EGP',
            'branch_id' => null,
        ];

        $lines = [];

        // =========================
        // 1) Debit side (Payments + Remaining on AR)
        // =========================
        $sumPayments = round((float) $invoice->payments->sum('amount'), 4);

        if ($sumPayments > 0.0001) {
            $byMethod = $invoice->payments
                ->groupBy('method')
                ->map(fn ($g) => round((float) $g->sum('amount'), 4))
                ->toArray();

            $cash = (float) ($byMethod['cash'] ?? 0);
            $card = (float) ($byMethod['card'] ?? 0);
            $wallet = (float) ($byMethod['wallet'] ?? 0);

            if ($cash > 0) {
                $lines[] = ['account_id' => $cashAccountId,   'debit' => $cash,   'credit' => 0, 'memo' => 'Cash'];
            }
            if ($card > 0) {
                $lines[] = ['account_id' => $cardAccountId,   'debit' => $card,   'credit' => 0, 'memo' => 'Card'];
            }
            if ($wallet > 0) {
                $lines[] = ['account_id' => $walletAccountId, 'debit' => $wallet, 'credit' => 0, 'memo' => 'Wallet'];
            }

            $paid = round($cash + $card + $wallet, 4);
            $remaining = round($total - $paid, 4);

            if ($remaining > 0.0001) {
                $lines[] = ['account_id' => $arAccountId, 'debit' => $remaining, 'credit' => 0, 'memo' => 'A/R Remaining'];
            }

            if ($remaining < -0.0001) {
                throw new \RuntimeException("Payments exceed invoice total. paid={$paid} total={$total} invoice={$invoice->id}");
            }
        } else {
            // no payments saved
            if (($invoice->payment_type ?? 'cash') === 'cash') {
                $lines[] = ['account_id' => $cashAccountId, 'debit' => $total, 'credit' => 0, 'memo' => 'Cash Sale'];
            } else {
                $lines[] = ['account_id' => $arAccountId, 'debit' => $total, 'credit' => 0, 'memo' => 'A/R'];
            }
        }

        // =========================
        // 2) Credit side (Revenue + VAT)
        // Revenue = netBeforeInvoiceDiscount (after line discounts)
        // =========================
        if ($netBeforeInvDisc > 0.0001) {
            $lines[] = ['account_id' => $salesRevenueId, 'debit' => 0, 'credit' => $netBeforeInvDisc, 'memo' => 'Sales Revenue'];
        }

        if ($vat > 0.0001) {
            $lines[] = ['account_id' => $outputVatId, 'debit' => 0, 'credit' => $vat, 'memo' => 'VAT Output'];
        }

        // =========================
        // 2.1) Discount (Contra Revenue)
        // Dr Sales Discount Allowed
        // =========================
        if ($discount > 0.0001) {
            $lines[] = ['account_id' => $salesDiscountId, 'debit' => $discount, 'credit' => 0, 'memo' => 'Sales Discount'];
        }

        // =========================
        // 3) COGS / Inventory
        // =========================
        if ($cogs > 0.0001) {
            $lines[] = ['account_id' => $cogsId,      'debit' => $cogs, 'credit' => 0,     'memo' => 'COGS'];
            $lines[] = ['account_id' => $inventoryId, 'debit' => 0,     'credit' => $cogs, 'memo' => 'Inventory Out'];
        }

        // =========================
        // Safety check
        // =========================
        $totalDebit = round(array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $lines)), 4);
        $totalCredit = round(array_sum(array_map(fn ($l) => (float) ($l['credit'] ?? 0), $lines)), 4);

        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw new \RuntimeException("Journal entry not balanced for invoice {$invoice->id}. debit={$totalDebit} credit={$totalCredit}");
        }

        $je = app(LedgerWriter::class)->postEntry($payload, $lines);

        $invoice->update(['journal_entry_id' => $je->id]);

        // ✅ تسجيل حركات المخزون (sales_out) لكل صنف في الفاتورة
        if ($cogs > 0.0001) {
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
                    'movement_type' => 'sales_out',
                    'quantity' => $line->quantity,
                    'unit_cost' => $line->cost_price ?? 0,
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $invoice->id,
                    'notes' => "Sales Invoice {$invoice->invoice_code}",
                ]);
            }
        }
    }
}
