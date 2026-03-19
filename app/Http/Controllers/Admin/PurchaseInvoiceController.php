<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\PurchaseInvoicesDataTable;
use App\Models\Items;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends AdminBaseController
{
    public function index(PurchaseInvoicesDataTable $dataTable)
    {
        return $dataTable->render('admin.purchase_invoices.index');

    }

    public function create()
    {
        $ownerId = $this->ownerId();

        $suppliers = Supplier::where('user_id', $ownerId)->orderByDesc('id')->get();

        $itemsJs = Items::where('user_id', $ownerId)
            ->select('id', 'name', 'items_code as code', 'barcode', 'type', 'unit_id', 'retail_unit', 'does_has_retail_unit', 'retail_uom_quintToParent as factor')
            ->orderByDesc('id')
            ->get()
            ->toArray();

        return view('admin.purchase_invoices.create', compact('suppliers', 'itemsJs'));
    }

    public function store(Request $request)
    {
        $ownerId = $this->ownerId();
        $data = $this->validateInvoice($request);

        return DB::transaction(function () use ($data, $ownerId) {

            $actor = auth()->user()->name;

            // ✅ خصم الفاتورة: لو fixed نخزن قيمته في discount_value
            $invDiscountValue = 0;
            if (($data['discount_type'] ?? 'none') === 'fixed') {
                $invDiscountValue = (float) ($data['discount_rate'] ?? 0);
            }

            $invoice = PurchaseInvoice::create([
                'user_id' => $ownerId,
                'supplier_id' => $data['supplier_id'],

                'purchase_invoice_code' => $this->nextPurchaseInvoiceCode($ownerId),

                'invoice_number' => $data['invoice_number'],

                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'transaction_id' => $data['transaction_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,

                'payment_type' => $data['payment_type'],
                'due_date' => $data['due_date'] ?? null,

                'currency_code' => $data['currency_code'] ?? 'EGP',
                'exchange_rate' => $data['exchange_rate'] ?? 1,

                'tax_included' => (bool) ($data['tax_included'] ?? false),

                'discount_type' => $data['discount_type'] ?? 'none',
                'discount_rate' => $data['discount_rate'] ?? null,
                'discount_value' => $invDiscountValue, // ✅ مهم

                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'other_charges' => $data['other_charges'] ?? 0,

                'status' => $data['status'] ?? 'draft',

                'notes' => $data['notes'] ?? null,
                'date' => now()->toDateString(),
                'updated_by' => $actor,
            ]);

            $this->syncItems($invoice, $data['items'] ?? [], $actor);
            $this->recalcInvoice($invoice);

            return redirect()
                ->route('purchase_invoices.show', $invoice->id)
                ->with('success', 'تم إنشاء فاتورة المشتريات بنجاح');
        });
    }

    public function show($id)
    {
        $ownerId = $this->ownerId();
        $invoice = PurchaseInvoice::where('user_id', $ownerId)->findOrFail($id);

        return view('admin.purchase_invoices.show', compact('invoice'));
    }

    public function edit($id)
    {
        $ownerId = $this->ownerId();

        $invoice = PurchaseInvoice::with(['items'])
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $suppliers = Supplier::where('user_id', $ownerId)->orderByDesc('id')->get();

        $itemsJs = Items::where('user_id', $ownerId)
            ->select('id', 'name', 'items_code as code', 'barcode', 'type', 'unit_id', 'retail_unit', 'does_has_retail_unit', 'retail_uom_quintToParent as factor')
            ->orderByDesc('id')
            ->get()
            ->toArray();

        return view('admin.purchase_invoices.edit', compact('invoice', 'suppliers', 'itemsJs'));
    }

    public function update(Request $request, $id)
    {
        $ownerId = $this->ownerId();
        $ownerId = $this->ownerId();

        $invoice = PurchaseInvoice::where('user_id', $ownerId)->findOrFail($id);
        if (in_array($invoice->status, ['posted', 'paid', 'partial'], true)) {
            return back()->with('error', 'لا يمكن تعديل فاتورة مُرحّلة. للتصحيح: أنشئ إشعار خصم/إضافة أو فاتورة جديدة أو قم بإلغاء الترحيل عبر قيد عكسي (حسب صلاحياتك).');
        }
        $data = $this->validateInvoice($request, true);

        return DB::transaction(function () use ($data, $id, $ownerId) {

            $invoice = PurchaseInvoice::where('user_id', $ownerId)->findOrFail($id);
            $actor = auth()->user()->name;

            // ✅ خصم الفاتورة: لو fixed نخزن قيمته في discount_value
            $invDiscountValue = 0;
            if (($data['discount_type'] ?? 'none') === 'fixed') {
                $invDiscountValue = (float) ($data['discount_rate'] ?? 0);
            }

            $invoice->update([
                'supplier_id' => $data['supplier_id'],

                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'] ?? $invoice->invoice_date,

                'transaction_id' => $data['transaction_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,

                'payment_type' => $data['payment_type'],
                'due_date' => $data['due_date'] ?? null,

                'currency_code' => $data['currency_code'] ?? $invoice->currency_code,
                'exchange_rate' => $data['exchange_rate'] ?? $invoice->exchange_rate,

                'tax_included' => (bool) ($data['tax_included'] ?? $invoice->tax_included),

                'discount_type' => $data['discount_type'] ?? 'none',
                'discount_rate' => $data['discount_rate'] ?? null,
                'discount_value' => $invDiscountValue, // ✅ مهم

                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'other_charges' => $data['other_charges'] ?? 0,

                'notes' => $data['notes'] ?? null,
                'updated_by' => $actor,
            ]);

            $invoice->items()->delete();
            $this->syncItems($invoice, $data['items'] ?? [], $actor);

            $this->recalcInvoice($invoice);

            return redirect()
                ->route('purchase_invoices.show', $invoice->id)
                ->with('success', 'تم تعديل فاتورة المشتريات بنجاح');
        });
    }

    public function post($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $invoice = PurchaseInvoice::with(['items'])
                ->where('user_id', $ownerId)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($invoice->status === 'cancelled') {
                return back()->with('error', 'لا يمكن ترحيل فاتورة ملغاة');
            }

            if (! $invoice->items()->exists()) {
                return back()->with('error', 'لا يمكن ترحيل فاتورة بدون أصناف');
            }

            if (! empty($invoice->journal_entry_id)) {
                return back()->with('info', 'الفاتورة مُرحّلة بالفعل');
            }

            $invoice->update([
                'status' => $invoice->status === 'draft' ? 'posted' : $invoice->status,
                'posted_at' => now(),
                'posted_by' => auth()->user()->name,
                'updated_by' => auth()->user()->name,
            ]);

            $this->recalcInvoice($invoice);

            app(\App\Services\Accounting\PostPurchaseInvoiceToLedger::class)->handle(
                tenantId: $ownerId,
                invoice: $invoice->fresh(),
                actorUserId: auth()->id()
            );

            return back()->with('success', 'تم ترحيل فاتورة المشتريات + إنشاء قيد يومية');
        });
    }

    public function cancel($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $invoice = PurchaseInvoice::where('user_id', $ownerId)
                ->lockForUpdate()
                ->findOrFail($id);

            // ✅ لو ملغاة خلاص
            if ($invoice->status === 'cancelled') {
                return back()->with('info', 'الفاتورة ملغاة بالفعل');
            }

            // ✅ لو مترحّلة: اعمل reversal
            if (! empty($invoice->journal_entry_id)) {
                app(\App\Services\Accounting\ReverseJournalEntry::class)->handle(
                    tenantId: $ownerId,
                    entryId: (int) $invoice->journal_entry_id,
                    actorUserId: auth()->id(),
                    reason: "Cancel Purchase Invoice {$invoice->purchase_invoice_code}"
                );

            }

            $invoice->update([
                'status' => 'cancelled',
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'posted_at' => null,
                'posted_by' => null,
                'updated_by' => auth()->user()->name,
            ]);

            // ✅ إعادة حساب لتصفير الإجماليات عندك (حسب recalcInvoice اللي عاملها)
            $this->recalcInvoice($invoice);

            return back()->with('success', 'تم إلغاء فاتورة المشتريات');
        });
    }

    public function destroy($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $invoice = PurchaseInvoice::where('user_id', $ownerId)->findOrFail($id);

            $invoice->items()->delete();
            $invoice->delete();

            return redirect()
                ->route('purchase_invoices.index')
                ->with('success', 'تم حذف فاتورة المشتريات');
        });
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function validateInvoice(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'supplier_id' => ['required', 'integer'],
            'invoice_number' => ['required', 'string', 'max:255'],

            'invoice_date' => ['nullable', 'date'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'purchase_order_id' => ['nullable', 'string', 'max:255'],

            'payment_type' => ['required', 'in:cash,credit'],
            'due_date' => ['nullable', 'date'],

            'currency_code' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],

            'tax_included' => ['nullable', 'boolean'],

            'discount_type' => ['nullable', 'in:none,percent,fixed'],
            // ✅ هنا التعديل المهم: ماينفعش max:100 لأن fixed مبلغ
            'discount_rate' => ['nullable', 'numeric', 'min:0'],

            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],

            'status' => ['nullable', 'in:draft,posted,paid,partial,cancelled'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.warehouse_name_snapshot' => ['nullable', 'string', 'max:255'],
            'items.*.transaction_id' => ['nullable', 'string', 'max:255'],

            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],

            'items.*.discount_type' => ['nullable', 'in:none,percent,fixed'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],

            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        $data = $request->validate($rules);

        // ✅ Validation شرطية: لو percent يبقى max 100
        if (($data['discount_type'] ?? 'none') === 'percent') {
            if (isset($data['discount_rate']) && (float) $data['discount_rate'] > 100) {
                // throw validation error
                validator($data, [])->after(function ($v) {
                    $v->errors()->add('discount_rate', 'نسبة الخصم لا تتجاوز 100%.');
                })->validate();
            }
        }

        // قواعد كاش/آجل
        if (($data['payment_type'] ?? 'credit') === 'cash') {
            $data['due_date'] = null;
        } else {
            if (empty($data['due_date'])) {
                $data['due_date'] = now()->addDays(30)->toDateString();
            }
        }

        return $data;
    }

    private function syncItems(PurchaseInvoice $invoice, array $items, string $actor): void
    {
        $ownerId = $this->ownerId();

        foreach ($items as $row) {

            $qty = round((float) $row['quantity'], 2);
            $unit = round((float) $row['unit_price'], 2);
            $lineSub = round($qty * $unit, 2);

            $dType = $row['discount_type'] ?? 'none';
            $dRate = isset($row['discount_rate']) ? (float) $row['discount_rate'] : null;

            $dValue = 0.0;
            if ($dType === 'percent') {
                $dValue = round($lineSub * (($dRate ?? 0) / 100), 2);
            } elseif ($dType === 'fixed') {
                $dValue = round(min($lineSub, (float) ($row['discount_value'] ?? 0)), 2);
            }

            $afterDiscount = max(0, round($lineSub - $dValue, 2));

            $tRate = isset($row['tax_rate']) ? (float) $row['tax_rate'] : 0;
            $tValue = round($afterDiscount * ($tRate / 100), 2);

            $lineTotal = round($afterDiscount + $tValue, 2);

            PurchaseInvoiceItem::create([
                'user_id' => $ownerId,
                'purchase_invoice_id' => $invoice->id,
                'item_id' => $row['item_id'],

                'warehouse_name_snapshot' => $row['warehouse_name_snapshot'] ?? null,
                'transaction_id' => $row['transaction_id'] ?? null,

                'quantity' => $qty,
                'unit_price' => $unit,

                'discount_type' => $dType,
                'discount_rate' => $dType === 'percent' ? $dRate : null,
                'discount_value' => $dValue,

                'tax_rate' => $tRate ?: null,
                'tax_value' => $tValue,

                'line_subtotal' => $lineSub,
                'line_total' => $lineTotal,

                'date' => now()->toDateString(),
                'updated_by' => $actor,
            ]);
        }
    }

    private function recalcInvoice(PurchaseInvoice $invoice): void
    {
        $invoice->load(['items']);

        if ($invoice->status === 'cancelled') {
            $invoice->update([
                'subtotal_before_discount' => 0,
                'discount_value' => 0,
                'subtotal' => 0,
                'tax_value' => 0,
                'total' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
            ]);

            return;
        }

        $subtotalBeforeDiscount = round((float) $invoice->items->sum('line_subtotal'), 2);
        $linesTaxTotal = round((float) $invoice->items->sum('tax_value'), 2);
        $linesDiscountTotal = round((float) $invoice->items->sum('discount_value'), 2);

        // subtotal after line discounts
        $subtotalAfterLineDiscounts = max(0, round($subtotalBeforeDiscount - $linesDiscountTotal, 2));

        // ✅ invoice-level discount
        $invDiscType = $invoice->discount_type ?? 'none';
        $invDiscRate = (float) ($invoice->discount_rate ?? 0);

        $invDiscValue = 0.0;

        if ($invDiscType === 'percent') {
            $invDiscValue = round($subtotalAfterLineDiscounts * ($invDiscRate / 100), 2);
        } elseif ($invDiscType === 'fixed') {
            // ✅ القيمة المخزنة (جاية من input discount_rate لما النوع fixed)
            $fixedAmount = (float) ($invoice->discount_value ?? 0);
            $invDiscValue = round(min($subtotalAfterLineDiscounts, $fixedAmount), 2);
        }

        $subtotalAfterAllDiscounts = max(0, round($subtotalAfterLineDiscounts - $invDiscValue, 2));

        $shipping = round((float) ($invoice->shipping_cost ?? 0), 2);
        $other = round((float) ($invoice->other_charges ?? 0), 2);

        $taxTotal = $linesTaxTotal;
        $total = round($subtotalAfterAllDiscounts + $taxTotal + $shipping + $other, 2);

        $paid = round((float) ($invoice->paid_amount ?? 0), 2);

        if ($invoice->payment_type === 'cash' && in_array($invoice->status, ['posted', 'paid', 'partial'], true)) {
            $paid = $total;
        }

        $status = $invoice->status;
        if (! in_array($status, ['draft', 'cancelled'], true)) {
            if ($paid <= 0) {
                $status = 'posted';
            } elseif ($paid >= $total) {
                $status = 'paid';
            } else {
                $status = 'partial';
            }
        }

        $remaining = max(0, round($total - $paid, 2));

        $invoice->update([
            'subtotal_before_discount' => $subtotalBeforeDiscount,

            // ✅ نخزن هنا الخصم النهائي (سواء percent أو fixed) في discount_value
            'discount_value' => $invDiscValue,

            'subtotal' => $subtotalAfterAllDiscounts,
            'tax_value' => $taxTotal,
            'total' => $total,

            'paid_amount' => $paid,
            'remaining_amount' => $remaining,

            'status' => $status,
            'updated_by' => auth()->user()->name,
        ]);
    }

    public function pdf($id)
    {
        $ownerId = $this->ownerId();

        $invoice = PurchaseInvoice::with([
            'supplier:id,name,phone,code',
            'items.item:id,name,items_code,barcode',
        ])
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $setting = \App\Models\Settings::where('user_id', $ownerId)
            ->where('status', true)
            ->first();

        // ✅ Arabic Shaping (ar-php/ar-php PSR-0 "Arphp")
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

        // ✅ IMPORTANT: don't shape MIXED Arabic+Latin/Numbers (DomPDF will mess order)
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

            // no shaping for non-arabic, or mixed arabic+latin/numbers, or missing lib
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

        $pdf = Pdf::loadView('admin.purchase_invoices.pdf', [
            'invoice' => $invoice,
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

        return $pdf->stream("purchase-invoice-{$invoice->invoice_number}.pdf");
    }

    private function nextPurchaseInvoiceCode(int $ownerId): string
    {
        $prefix = 'PI-';

        $lastInvoice = PurchaseInvoice::where('user_id', $ownerId)
            ->where('purchase_invoice_code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $lastNumber = 0;

        if ($lastInvoice?->purchase_invoice_code) {
            $digits = preg_replace('/\D+/', '', $lastInvoice->purchase_invoice_code);
            $lastNumber = (int) ($digits ?: 0);
        }

        $next = $lastNumber + 1;

        return $prefix.str_pad((string) $next, 8, '0', STR_PAD_LEFT); // PI-00000001
    }
}
