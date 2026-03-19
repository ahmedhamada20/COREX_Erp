<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;

class PostSupplierPayment
{
    public function handle(
        int $tenantId,
        PurchaseInvoice $invoice,
        float $amount,
        string $actorName,
        string $paymentDate,
        ?int $cashAccountId = null
    ): JournalEntry {
        $amount = (float) $amount;
        if ($amount <= 0) {
            throw new \InvalidArgumentException('مبلغ السداد يجب أن يكون أكبر من صفر.');
        }

        return DB::transaction(function () use ($tenantId, $invoice, $amount, $actorName, $paymentDate, $cashAccountId) {

            $invoice->refresh();
            $invoice->load(['supplier']);

            if (in_array($invoice->status, ['draft', 'cancelled'], true)) {
                throw new \RuntimeException('لا يمكن السداد لفاتورة مسودة أو ملغاة.');
            }

            $total = (float) ($invoice->total ?? 0);
            $paid = (float) ($invoice->paid_amount ?? 0);
            $remaining = max(0, $total - $paid);

            if ($amount - $remaining > 0.0001) {
                throw new \RuntimeException("المبلغ أكبر من المتبقي. المتبقي: {$remaining}");
            }

            $resolver = app(AccountResolver::class);

            // ✅ Supplier sub-ledger or AP control
            $supplierAccId = (int) ($invoice->supplier?->account_id ?? 0);
            if ($supplierAccId <= 0) {
                $supplierAccId = $resolver->apControl($tenantId);
            }

            // ✅ Cash account (either passed or default)
            $cashAccId = $cashAccountId ?: $resolver->cash($tenantId);

            // validate cash acc belongs to tenant
            $existsCash = Account::query()
                ->where('user_id', $tenantId)
                ->whereKey($cashAccId)
                ->exists();

            if (! $existsCash) {
                throw new \RuntimeException("Invalid cash account_id={$cashAccId} for tenant={$tenantId}");
            }

            $amount = round($amount, 2);

            $entryNumber = app(JournalEntryNumberGenerator::class)->next($tenantId, new \DateTime($paymentDate));

            $payload = [
                'user_id' => $tenantId,
                'entry_number' => $entryNumber,
                'entry_date' => $paymentDate,
                'source' => 'supplier_payment',
                'reference_type' => PurchaseInvoice::class,
                'reference_id' => $invoice->id,
                'description' => "سند صرف - فاتورة مشتريات #{$invoice->purchase_invoice_code}",
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => null,
                'currency_code' => $invoice->currency_code ?? 'EGP',
                'branch_id' => $invoice->branch_id ?? null,
            ];

            // Dr A/P (supplier) , Cr Cash
            $lines = [
                ['account_id' => $supplierAccId, 'debit' => $amount, 'credit' => 0, 'memo' => 'Supplier payment'],
                ['account_id' => $cashAccId,     'debit' => 0,       'credit' => $amount, 'memo' => 'Cash/Bank out'],
            ];

            $entry = app(LedgerWriter::class)->postEntry($payload, $lines);

            // ✅ Update invoice paid/status
            $newPaid = round($paid + $amount, 2);
            $newRemaining = max(0, round($total - $newPaid, 2));

            $newStatus = 'posted';
            if ($newPaid <= 0) {
                $newStatus = 'posted';
            } elseif ($newRemaining <= 0.0001) {
                $newStatus = 'paid';
            } else {
                $newStatus = 'partial';
            }

            $invoice->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $newRemaining,
                'status' => $newStatus,
                'updated_by' => $actorName,
            ]);

            return $entry;
        });
    }
}
