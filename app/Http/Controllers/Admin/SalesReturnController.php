<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\SalesReturnsDataTable;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends AdminBaseController
{
    public function index(SalesReturnsDataTable $dataTable)
    {
        return $dataTable->render('admin.sales_returns.index');
    }

    // ✅ Create return from invoice
    public function createFromInvoice($invoiceId)
    {
        $ownerId = $this->ownerId();

        $invoice = SalesInvoice::where('user_id', $ownerId)
            ->with([
                'customer:id,name,phone,code',
                'items.item:id,name,items_code,barcode,type',
                'payments:id,sales_invoice_id,amount,method,treasury_id',

                'returns:id,sales_invoice_id,total,journal_entry_id',
            ])
            ->findOrFail((int) $invoiceId);

        // ✅ ممنوع مرتجع لفاتورة ملغاة أو غير مرحلة
        if (! in_array($invoice->status, ['posted', 'paid', 'partial'], true)) {
            return back()->with('error', 'لا يمكن إنشاء مرتجع إلا لفاتورة مُرحّلة وغير ملغاة.');
        }

        // ✅ مجموع المرتجعات السابقة (واستبعد الملغي: عندك الإلغاء بيصفّر total)
        $alreadyReturned = round((float) ($invoice->returns?->sum('total') ?? 0), 4);

        // ✅ المتبقي القابل للمرتجع
        $remainingReturnable = round((float) $invoice->total - $alreadyReturned, 4);

        if ($remainingReturnable <= 0.0001) {
            return back()->with('error', 'تم عمل مرتجع كامل لهذه الفاتورة بالفعل ولا يوجد مبلغ متبقي للمرتجع.');
        }

        // ✅ أقصى مرتجع = المتبقي فقط
        $maxReturn = $remainingReturnable;

        return view('admin.sales_returns.create_from_invoice', compact('invoice', 'maxReturn', 'alreadyReturned', 'remainingReturnable'));
    }

    public function storeFromInvoice(Request $request, $invoiceId)
    {
        $ownerId = $this->ownerId();

        $data = $request->validate([
            'return_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'refund_mode' => ['required', 'in:auto,cash,ar'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($data, $ownerId, $invoiceId) {

            /** @var SalesInvoice $invoice */
            $invoice = SalesInvoice::where('user_id', $ownerId)
                ->lockForUpdate()
                ->with('customer')
                ->findOrFail((int) $invoiceId);

            // ✅ ممنوع مرتجع لفاتورة ملغاة أو غير مرحلة
            if (! in_array($invoice->status, ['posted', 'paid', 'partial'], true)) {
                return back()->with('error', 'لا يمكن إنشاء مرتجع إلا لفاتورة مُرحّلة وغير ملغاة.');
            }

            // ✅ احسب إجمالي المرتجعات السابقة (الملغي عندك total=0 فمش هيأثر)
            // IMPORTANT: نخليها داخل نفس الترانزاكشن بعد lockForUpdate
            $alreadyReturned = (float) SalesReturn::where('user_id', $ownerId)
                ->where('sales_invoice_id', $invoice->id)
                ->sum('total');

            $alreadyReturned = round($alreadyReturned, 4);

            // ✅ المتبقي القابل للمرتجع
            $remainingReturnable = round((float) $invoice->total - $alreadyReturned, 4);

            if ($remainingReturnable <= 0.0001) {
                return back()->with('error', 'تم عمل مرتجع كامل لهذه الفاتورة بالفعل ولا يوجد مبلغ متبقي للمرتجع.');
            }

            $amount = round((float) $data['amount'], 4);

            // ✅ امنع تجاوز المتبقي (مش إجمالي الفاتورة)
            if ($amount - $remainingReturnable > 0.0001) {
                return back()->with('error', 'قيمة المرتجع أكبر من المتبقي القابل للمرتجع. المتبقي: '.$remainingReturnable);
            }

            // ✅ VAT حالياً = 0 (Header-only)
            $subtotal = $amount;
            $vat = 0.0;
            $total = $amount;

            $payload = [
                'user_id' => $ownerId,
                'customer_id' => $invoice->customer_id,
                'sales_invoice_id' => $invoice->id,
                'return_date' => $data['return_date'] ?? now()->toDateString(),
                'subtotal' => $subtotal,
                'vat_amount' => $vat,
                'total' => $total,
                'journal_entry_id' => null,
            ];

            // ✅ لو عندك notes column فعلاً
            if (array_key_exists('notes', $data) && isset($data['notes'])) {
                $payload['notes'] = $data['notes'];
            }

            $return = SalesReturn::create($payload);

            // ✅ Accounting posting
            app(\App\Services\Accounting\PostSalesReturnToLedger::class)->handle(
                tenantId: $ownerId,
                return: $return->fresh(),
                actorUserId: auth()->id(),
                refundMode: $data['refund_mode'] ?? 'auto'
            );

            return redirect()
                ->route('sales_returns.show', $return->id)
                ->with('success', 'تم إنشاء مرتجع من الفاتورة + إنشاء قيد يومية');
        });
    }

    public function show($id)
    {
        $ownerId = $this->ownerId();

        $return = SalesReturn::with([
            'customer:id,name,phone,code',
            'invoice:id,invoice_code,invoice_number,invoice_date,total',
        ])->where('user_id', $ownerId)->findOrFail($id);

        return view('admin.sales_returns.show', compact('return'));
    }

    public function edit($id): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $ownerId = $this->ownerId();

        $return = SalesReturn::with([
            'customer:id,name,phone,code',
            'invoice:id,invoice_code,invoice_number,invoice_date,total',
        ])->where('user_id', $ownerId)->findOrFail($id);

        if (! empty($return->journal_entry_id)) {
            return back()->with('error', 'لا يمكن تعديل مرتجع مُرحّل إلى دفتر الأستاذ.');
        }

        return view('admin.sales_returns.edit', compact('return'));
    }

    public function update(Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        $ownerId = $this->ownerId();

        $data = $request->validate([
            'return_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($data, $ownerId, $id) {
            $return = SalesReturn::where('user_id', $ownerId)
                ->lockForUpdate()
                ->findOrFail($id);

            if (! empty($return->journal_entry_id)) {
                return back()->with('error', 'لا يمكن تعديل مرتجع مُرحّل.');
            }

            $return->update([
                'return_date' => $data['return_date'] ?? $return->return_date,
                'notes' => $data['notes'] ?? $return->notes,
            ]);

            return redirect()
                ->route('sales_returns.show', $return->id)
                ->with('success', 'تم تعديل المرتجع بنجاح');
        });
    }

    public function cancel($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $return = SalesReturn::where('user_id', $ownerId)
                ->lockForUpdate()
                ->findOrFail($id);

            // ✅ منع تكرار الإلغاء
            if ((float) $return->total <= 0.0001) {
                return back()->with('info', 'تم إلغاء المرتجع بالفعل.');
            }

            if (! empty($return->journal_entry_id)) {
                app(\App\Services\Accounting\ReverseJournalEntry::class)->handle(
                    tenantId: $ownerId,
                    entryId: (int) $return->journal_entry_id,
                    actorUserId: auth()->id(),
                    reason: "Cancel Sales Return {$return->id}"
                );
            }

            // تصفير القيم (لأن مفيش status column حالياً)
            $return->update([
                'subtotal' => 0,
                'vat_amount' => 0,
                'total' => 0,
            ]);

            return back()->with('success', 'تم إلغاء مرتجع المبيعات');
        });
    }

    public function pdf($id)
    {
        $ownerId = $this->ownerId();

        $return = \App\Models\SalesReturn::with([
            'customer:id,name,phone,code',
            'invoice:id,invoice_code,invoice_number,invoice_date,total,payment_type,due_date',
        ])
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $setting = \App\Models\Settings::where('user_id', $ownerId)
            ->where('status', true)
            ->first();

        // ✅ Arabic Shaping (ar-php/ar-php)
        $arabic = null;
        if (class_exists('\Arphp\I18N\Arabic')) {
            $arabic = new \Arphp\I18N\Arabic;
        } elseif (class_exists('\ArPHP\I18N\Arabic')) {
            $arabic = new \ArPHP\I18N\Arabic;
        }

        $hasArabic = static function (?string $text): bool {
            if (! $text) {
                return false;
            }

            return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $text);
        };

        // ✅ IMPORTANT: don't shape MIXED Arabic+Latin/Numbers (DomPDF may reverse)
        $isMixed = static function (?string $text): bool {
            if (! $text) {
                return false;
            }

            return (bool) preg_match('/[A-Za-z0-9]/', $text);
        };

        $shape = static function (?string $text) use ($arabic, $hasArabic, $isMixed): string {
            $text = (string) ($text ?? '');
            if ($text === '') {
                return '';
            }

            if (! $arabic || ! $hasArabic($text) || $isMixed($text)) {
                return $text;
            }

            if (method_exists($arabic, 'utf8Glyphs')) {
                return $arabic->utf8Glyphs($text);
            }
            if (method_exists($arabic, 'glyphs')) {
                return $arabic->glyphs($text);
            }

            return $text;
        };

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.sales_returns.pdf', [
            'return' => $return,
            'setting' => $setting,
            'shape' => $shape,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'Cairo',
            ]);

        $code = $return->id;
        if (! empty($return->sales_return_code)) {
            $code = $return->sales_return_code;
        }

        return $pdf->stream("sales-return-{$code}.pdf");
    }
}
