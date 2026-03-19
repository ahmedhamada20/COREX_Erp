<?php

namespace App\Http\Controllers\Admin;

use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesPaymentController extends AdminBaseController
{
    public function store(Request $request, $invoiceId)
    {
        $ownerId = $this->ownerId();

        $invoice = SalesInvoice::where('user_id', $ownerId)
            ->lockForUpdate()
            ->findOrFail($invoiceId);

        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'لا يمكن إضافة تحصيل لفاتورة ملغاة');
        }

        if ($invoice->payment_type === 'cash') {
            return back()->with('error', 'هذه فاتورة كاش. لا يُسمح بإضافة تحصيلات منفصلة.');
        }

        $data = $request->validate([
            'treasury_id' => ['required', 'integer'],
            'method' => ['required', 'in:cash,card,wallet'],
            'terminal_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($invoice, $data, $ownerId) {

            // ✅ السيرفس هي اللي بتعمل payment + JE + link
            app(\App\Services\Accounting\PostSalesPaymentToLedger::class)->handle(
                tenantId: $ownerId,
                invoice: $invoice->fresh(),
                treasuryId: (int) $data['treasury_id'],
                amount: (float) $data['amount'],
                paymentDate: (string) $data['payment_date'],
                reference: $data['reference'] ?? null,
                method: (string) $data['method'],
                terminalId: $data['terminal_id'] ? (int) $data['terminal_id'] : null,
                actorUserId: auth()->id()
            );

            // ✅ Recalc status from payments (مصدر الحقيقة)
            $invoice->refresh();
            // عندك recalcInvoice في SalesInvoiceController؛ استدعِه من خدمة/trait لو تحب
            // هنا نعمل تحديث سريع:
            $paid = (float) SalesPayment::where('sales_invoice_id', $invoice->id)->sum('amount');
            $paid = round($paid, 4);

            $remaining = max(0, round((float) $invoice->total - $paid, 4));

            $status = $invoice->status;
            if (! in_array($status, ['draft', 'cancelled'], true)) {
                if ($paid <= 0.0001) {
                    $status = 'posted';
                } elseif ($remaining <= 0.0001) {
                    $status = 'paid';
                } else {
                    $status = 'partial';
                }
            }

            $invoice->update([
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'status' => $status,
                'updated_by' => auth()->user()->name,
            ]);

            return back()->with('success', 'تم إضافة التحصيل بنجاح');
        });
    }

    public function destroy($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $payment = SalesPayment::with('invoice')
                ->lockForUpdate()
                ->findOrFail($id);

            if ((int) $payment->invoice->user_id !== $ownerId) {
                abort(403);
            }

            if ($payment->invoice->status === 'cancelled') {
                return back()->with('error', 'الفاتورة ملغاة');
            }

            if (! empty($payment->journal_entry_id)) {
                app(\App\Services\Accounting\ReverseJournalEntry::class)->handle(
                    tenantId: $ownerId,
                    entryId: (int) $payment->journal_entry_id,
                    actorUserId: auth()->id(),
                    reason: "Cancel Sales Payment {$payment->id}"
                );
            }

            $invoice = $payment->invoice;
            $payment->delete();

            $paid = (float) SalesPayment::where('sales_invoice_id', $invoice->id)->sum('amount');
            $paid = round($paid, 4);

            $remaining = max(0, round((float) $invoice->total - $paid, 4));

            $status = $invoice->status;
            if (! in_array($status, ['draft', 'cancelled'], true)) {
                if ($paid <= 0.0001) {
                    $status = 'posted';
                } elseif ($remaining <= 0.0001) {
                    $status = 'paid';
                } else {
                    $status = 'partial';
                }
            }

            $invoice->update([
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'status' => $status,
                'updated_by' => auth()->user()->name,
            ]);

            return back()->with('success', 'تم حذف التحصيل');
        });
    }
}
