<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;

class PostSalesReturnToLedger
{
    /**
     * @param  string  $refundMode  auto|cash|ar
     */
    public function handle(int $tenantId, SalesReturn $return, int $actorUserId, string $refundMode = 'auto'): void
    {
        if (! empty($return->journal_entry_id)) {
            return;
        }

        $return->load(['invoice.customer']);

        /** @var SalesInvoice|null $invoice */
        $invoice = $return->invoice;

        if (! $invoice) {
            throw new \RuntimeException("SalesReturn {$return->id} has no invoice relation.");
        }

        // ✅ منع المرتجع لفاتورة ملغاة أو غير مرحلة (مهم محاسبيًا)
        if (! in_array($invoice->status, ['posted', 'paid', 'partial'], true)) {
            throw new \RuntimeException("Cannot post sales return for invoice status={$invoice->status}. invoice_id={$invoice->id}");
        }

        $resolver = app(AccountResolver::class);

        // Accounts
        $cashAccountId = $resolver->cash($tenantId);
        $salesReturnsId = $resolver->salesReturns($tenantId);
        $outputVatId = $resolver->outputVat($tenantId);

        // =========================
        // ✅ Resolve A/R account (customer sub-ledger) safely
        // Prefer customer.account_id if you have it, otherwise fallback
        // =========================
        $arAccountId = (int) ($invoice->customer?->account_id ?? 0);

        if ($arAccountId <= 0) {
            $customerAccNo = (string) ($invoice->customer?->account_number ?? '');
            if ($customerAccNo !== '') {
                $accId = Account::query()
                    ->where('user_id', $tenantId)
                    ->where('account_number', $customerAccNo)
                    ->value('id');

                if ($accId) {
                    $arAccountId = (int) $accId;
                }
            }
        }

        if ($arAccountId <= 0) {
            $arAccountId = (int) $resolver->arControl($tenantId);
        }

        // =========================
        // Amounts (4 decimals)
        // =========================
        $subtotal = round((float) ($return->subtotal ?? 0), 4);
        $vat = round((float) ($return->vat_amount ?? 0), 4);
        $total = round((float) ($return->total ?? 0), 4);

        if ($total <= 0.0001) {
            throw new \RuntimeException("Invalid sales return total. return_id={$return->id} total={$total}");
        }

        // ✅ consistency check: total should equal subtotal + vat (within tolerance)
        if (abs(($subtotal + $vat) - $total) > 0.0002) {
            // مش لازم تفشل النظام لو عندك rounding من UI، لكن الأفضل تعرف
            // تقدر throw لو تحب
            throw new \RuntimeException("Sales return amounts inconsistent. subtotal={$subtotal} vat={$vat} total={$total} return_id={$return->id}");
        }

        // =========================
        // Decide credit account (where we credit the refund)
        // refundMode:
        // - auto: cash invoice => cash, credit invoice => AR
        // - cash: always cash
        // - ar: always AR
        // =========================
        $invoicePaymentType = (string) ($invoice->payment_type ?? 'cash');

        $creditAccountId = match ($refundMode) {
            'cash' => $cashAccountId,
            'ar' => $arAccountId,
            default => ($invoicePaymentType === 'cash' ? $cashAccountId : $arAccountId),
        };

        $entryDate = $return->return_date ?: now()->toDateString();
        $entryNumber = app(JournalEntryNumberGenerator::class)->next($tenantId, new \DateTime($entryDate));

        // ✅ description آمن بدون sales_return_code (لأنه غالبًا غير موجود)
        $invoiceCode = $invoice->invoice_code ?? $invoice->invoice_number ?? ('#'.$invoice->id);

        $payload = [
            'user_id' => $tenantId,
            'entry_number' => $entryNumber,
            'entry_date' => $entryDate,
            'source' => 'sales_return',
            'reference_type' => SalesReturn::class,
            'reference_id' => $return->id,
            'description' => "Sales Return for Invoice {$invoiceCode}",
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $actorUserId,
            'currency_code' => $invoice->currency_code ?? 'EGP',
            'branch_id' => $invoice->branch_id ?? null,
        ];

        $lines = [];

        // Dr Sales Returns (contra revenue)
        if ($subtotal > 0.0001) {
            $lines[] = [
                'account_id' => $salesReturnsId,
                'debit' => $subtotal,
                'credit' => 0,
                'memo' => 'Sales Return',
            ];
        }

        // Dr VAT Output Reverse (reduce liability)
        if ($vat > 0.0001) {
            $lines[] = [
                'account_id' => $outputVatId,
                'debit' => $vat,
                'credit' => 0,
                'memo' => 'VAT Output Reverse',
            ];
        }

        // Cr Cash/AR
        $lines[] = [
            'account_id' => $creditAccountId,
            'debit' => 0,
            'credit' => $total,
            'memo' => $creditAccountId === $cashAccountId ? 'Cash Refund' : 'A/R Reverse',
        ];

        // ✅ Safety check (اختياري لكنه مفيد هنا)
        $totalDebit = round(array_sum(array_map(fn ($l) => (float) ($l['debit'] ?? 0), $lines)), 4);
        $totalCredit = round(array_sum(array_map(fn ($l) => (float) ($l['credit'] ?? 0), $lines)), 4);

        if (abs($totalDebit - $totalCredit) > 0.0001) {
            throw new \RuntimeException("Sales return JE not balanced. debit={$totalDebit} credit={$totalCredit} return_id={$return->id}");
        }

        $je = app(LedgerWriter::class)->postEntry($payload, $lines);

        $return->update(['journal_entry_id' => $je->id]);
    }
}
