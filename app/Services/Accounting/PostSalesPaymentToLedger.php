<?php

namespace App\Services\Accounting;

use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Models\Treasuries;
use Illuminate\Support\Facades\DB;

class PostSalesPaymentToLedger
{
    public function handle(
        int $tenantId,
        SalesInvoice $invoice,
        int $treasuryId,
        float $amount,
        string $paymentDate,
        ?string $reference,
        string $method,          // ✅ cash/card/wallet
        ?int $terminalId,
        int $actorUserId
    ): SalesPayment {
        // ✅ الدفعات دي للآجل فقط
        if (($invoice->payment_type ?? 'credit') === 'cash') {
            throw new \RuntimeException('Cannot add payments for cash invoices.');
        }

        $amount = round((float) $amount, 4);
        if ($amount <= 0.0001) {
            throw new \RuntimeException('Invalid payment amount.');
        }

        return DB::transaction(function () use (
            $tenantId, $invoice, $treasuryId, $amount, $paymentDate, $reference, $method, $terminalId, $actorUserId
        ) {

            $invoice->load(['customer']);

            $resolver = app(AccountResolver::class);

            // ✅ Customer AR
            $arAccountId = (int) ($invoice->customer?->account_id ?? 0);
            if ($arAccountId <= 0) {
                $arAccountId = (int) $resolver->arControl($tenantId);
            }

            // ✅ Treasury account
            $treasury = Treasuries::query()
                ->where('user_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($treasuryId);

            $cashAccountId = (int) ($treasury->account_id ?? 0);
            if ($cashAccountId <= 0) {
                $cashAccountId = (int) $resolver->cash($tenantId);
            }

            // ✅ Create payment once (مصدر الحقيقة)
            $payment = SalesPayment::create([
                'sales_invoice_id' => $invoice->id,
                'treasury_id' => $treasuryId,
                'terminal_id' => $terminalId,
                'method' => $method,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'reference' => $reference,
            ]);

            $entryNumber = app(JournalEntryNumberGenerator::class)
                ->next($tenantId, new \DateTime($paymentDate));

            $payload = [
                'user_id' => $tenantId,
                'entry_number' => $entryNumber,
                'entry_date' => $paymentDate,
                'source' => 'sales_receipt',
                'reference_type' => SalesInvoice::class,
                'reference_id' => $invoice->id,
                'description' => "Sales Receipt {$invoice->invoice_code}".($reference ? " ({$reference})" : ''),
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actorUserId,
                'currency_code' => 'EGP',
                'branch_id' => null,
            ];

            $lines = [
                ['account_id' => $cashAccountId, 'debit' => $amount, 'credit' => 0,       'memo' => strtoupper($method).' Receipt'],
                ['account_id' => $arAccountId,   'debit' => 0,       'credit' => $amount, 'memo' => 'A/R Settlement'],
            ];

            $je = app(LedgerWriter::class)->postEntry($payload, $lines);

            $payment->update(['journal_entry_id' => $je->id]);

            return $payment;
        });
    }
}
