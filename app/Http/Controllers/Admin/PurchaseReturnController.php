<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\PurchaseReturnsDataTable;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Settings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends AdminBaseController
{
    // ==========================
    // CRUD
    // ==========================

    public function index(PurchaseReturnsDataTable $dataTable)
    {
        return $dataTable->render('admin.purchase_returns.index');
    }

    public function store(Request $request)
    {
        $ownerId = $this->ownerId();
        $data = $this->validateReturn($request);

        return DB::transaction(function () use ($data, $ownerId) {

            $actor = auth()->user()->name;

            $return = PurchaseReturn::create([
                'user_id' => $ownerId,

                // لو موجودين في جدول purchase_returns
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,

                'purchase_return_code' => $this->nextPurchaseReturnCode($ownerId),

                'return_number' => $data['return_number'] ?? null,
                'return_date' => now()->toDateString(),

                'transaction_id' => $data['transaction_id'] ?? null,

                'status' => $data['status'] ?? 'draft',

                'notes' => $data['notes'] ?? null,
                'date' => now()->toDateString(),
                'updated_by' => $actor,
            ]);

            $this->syncItems($return, $data['items'] ?? [], $actor);
            $this->recalcReturn($return);

            return redirect()
                ->route('purchase_returns.show', $return->id)
                ->with('success', 'تم إنشاء مرتجع المشتريات بنجاح');
        });
    }

    public function show($id)
    {
        $ownerId = $this->ownerId();

        $return = PurchaseReturn::with([
            'supplier:id,name,phone,code',
            'invoice:id,purchase_invoice_code,invoice_number,invoice_date,total',
            'items.item:id,name,items_code,barcode',
        ])
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        return view('admin.purchase_returns.show', compact('return'));
    }

    public function edit($id)
    {
        $ownerId = $this->ownerId();

        $return = PurchaseReturn::with([
            'supplier:id,name,phone,code',
            'items.item:id,name,items_code,barcode',
        ])
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        // ✅ محاسبيًا: التعديل مسموح للمسودة فقط
        if (($return->status ?? 'draft') !== 'draft') {
            return redirect()
                ->route('purchase_returns.show', $return->id)
                ->with('error', 'لا يمكن تعديل مرتجع مُرحّل أو مُلغى. للتصحيح: قم بإلغاء المرتجع أو أنشئ مرتجع جديد.');
        }

        // ✅ الفاتورة المرجعية (مقفولة)
        $invoice = PurchaseInvoice::with([
            'supplier:id,name,phone,code',
            'items.item:id,name,items_code,barcode',
        ])
            ->where('user_id', $ownerId)
            ->findOrFail($return->purchase_invoice_id);

        // ✅ نجيب كميات هذا المرتجع الحاليّة لكل سطر (purchase_invoice_item_id)
        $currentMap = $return->items
            ->groupBy('purchase_invoice_item_id')
            ->map(fn ($g) => (float) $g->sum('quantity'))
            ->toArray();

        // ✅ مجموع المرتجعات الأخرى (غير هذا المرتجع) على مستوى كل سطر فاتورة
        $otherReturned = PurchaseReturnItem::query()
            ->where('user_id', $ownerId)
            ->whereIn('purchase_invoice_item_id', $invoice->items->pluck('id'))
            ->whereHas('purchaseReturn', function ($q) use ($ownerId, $return) {
                $q->where('user_id', $ownerId)
                    ->where('id', '!=', $return->id)
                    ->whereNull('deleted_at')
                    ->whereIn('status', ['draft', 'posted']); // لو عايز تحسب posted فقط غيّرها
            })
            ->selectRaw('purchase_invoice_item_id, SUM(quantity) as qty')
            ->groupBy('purchase_invoice_item_id')
            ->pluck('qty', 'purchase_invoice_item_id')
            ->toArray();

        $items = $invoice->items->map(function ($invItem) use ($currentMap, $otherReturned) {

            $purchased = (float) ($invItem->quantity ?? 0);

            $currentQty = (float) ($currentMap[$invItem->id] ?? 0);
            $returnedOther = (float) ($otherReturned[$invItem->id] ?? 0);

            $available = max(0, round($purchased - $returnedOther, 2));

            return [
                'purchase_invoice_item_id' => $invItem->id,
                'item_id' => $invItem->item_id,
                'item_name' => $invItem->item?->name ?? '',
                'code' => $invItem->item?->items_code ?? null,
                'barcode' => $invItem->item?->barcode ?? null,

                'qty_purchased' => round($purchased, 2),

                // مهم للعرض
                'qty_returned_other' => round($returnedOther, 2),
                'qty_available' => $available,

                // مهم للـ edit: الكمية الحالية
                'qty_current_return' => round($currentQty, 2),

                'unit_price' => (float) ($invItem->unit_price ?? 0),
                'tax_rate' => (float) ($invItem->tax_rate ?? 0),
                'warehouse_name_snapshot' => $invItem->warehouse_name_snapshot ?? null,
            ];
        })->values()->toArray();

        return view('admin.purchase_returns.edit', compact('return', 'invoice', 'items'));
    }

    public function update(Request $request, $id)
    {
        $ownerId = $this->ownerId();

        // ✅ validation
        $data = $this->validateReturn($request, true);

        return DB::transaction(function () use ($data, $id, $ownerId) {

            $return = PurchaseReturn::where('user_id', $ownerId)->findOrFail($id);

            // ✅ محاسبيًا: التعديل مسموح للمسودة فقط
            if (($return->status ?? 'draft') !== 'draft') {
                return back()->with('error', 'لا يمكن تعديل مرتجع مُرحّل أو مُلغى. قم بإلغاء المرتجع أو إنشاء مرتجع جديد للتصحيح.');
            }

            $actor = auth()->user()->name;

            // ✅ ممنوع تغيير مرجع الفاتورة بعد الإنشاء (قانونيًا)
            $invoiceId = (int) ($data['purchase_invoice_id'] ?? $return->purchase_invoice_id);
            if ($invoiceId !== (int) $return->purchase_invoice_id) {
                return back()->with('error', 'لا يمكن تغيير الفاتورة المرجعية للمرتجع بعد إنشائه.');
            }

            // ✅ المورد يورث من الفاتورة (أضمن محاسبيًا)
            $invoice = PurchaseInvoice::where('user_id', $ownerId)
                ->select('id', 'supplier_id', 'invoice_number')
                ->findOrFail($return->purchase_invoice_id);

            // ✅ لازم يكون فيه بند واحد على الأقل بكمية > 0
            $items = $data['items'] ?? [];
            $hasQty = collect($items)->contains(fn ($r) => (float) ($r['quantity'] ?? 0) > 0);
            if (! $hasQty) {
                return back()->with('error', 'لا يمكن حفظ مرتجع بدون كميات. أدخل كمية مرتجع واحدة على الأقل.');
            }

            // ✅ Update header (بنفس منطق ERP)
            $return->update([
                'supplier_id' => $invoice->supplier_id,              // قفل المورد
                'purchase_invoice_id' => $return->purchase_invoice_id,       // قفل الفاتورة

                // ✅ return_number: خليه ثابت (جايلك من الفاتورة) — لو تحب تربطه دايمًا
                'return_number' => $return->return_number ?: ($invoice->invoice_number ?? null),

                'return_date' => $data['return_date'] ?? $return->return_date,
                'transaction_id' => $data['transaction_id'] ?? null,

                'notes' => $data['notes'] ?? null,
                'updated_by' => $actor,
            ]);

            // ✅ إعادة بناء البنود
            $return->items()->delete();

            // (اختياري) تجاهل البنود اللي qty=0 قبل sync
            $items = array_values(array_filter($items, fn ($r) => (float) ($r['quantity'] ?? 0) > 0));

            $this->syncItems($return, $items, $actor);

            // ✅ إعادة حساب الإجماليات
            $this->recalcReturn($return);

            return redirect()
                ->route('purchase_returns.show', $return->id)
                ->with('success', 'تم تعديل مرتجع المشتريات بنجاح');
        });
    }

    public function post($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $return = PurchaseReturn::with(['items', 'invoice'])
                ->where('user_id', $ownerId)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($return->status === 'cancelled') {
                return back()->with('error', 'لا يمكن ترحيل مرتجع ملغى');
            }

            if (! $return->items()->exists()) {
                return back()->with('error', 'لا يمكن ترحيل مرتجع بدون أصناف');
            }

            if (! empty($return->journal_entry_id)) {
                return back()->with('info', 'المرتجع مُرحّل بالفعل');
            }

            // ✅ لازم يكون مرتبط بفاتورة مُرحّلة (ERP rule)
            if (! $return->purchase_invoice_id) {
                return back()->with('error', 'لا يمكن ترحيل مرتجع بدون فاتورة مرجعية');
            }

            $invoice = PurchaseInvoice::where('user_id', $ownerId)
                ->lockForUpdate()
                ->select('id', 'status')
                ->findOrFail($return->purchase_invoice_id);

            if (! in_array($invoice->status, ['posted', 'paid', 'partial'], true) || $invoice->status === 'cancelled') {
                return back()->with('error', 'لا يمكن ترحيل مرتجع إلا لفاتورة مُرحّلة');
            }

            // ✅ دفاع أخير ضد over-return (اختياري لكن مهم)
            // يتحقق أن كل purchase_invoice_item_id لم يتخطى الكمية المشتراة
            $hasOver = PurchaseReturnItem::query()
                ->where('user_id', $ownerId)
                ->where('purchase_return_id', $return->id)
                ->whereNotNull('purchase_invoice_item_id')
                ->get()
                ->some(function ($r) use ($ownerId) {
                    $invItem = \App\Models\PurchaseInvoiceItem::where('user_id', $ownerId)
                        ->select('id', 'quantity')
                        ->find($r->purchase_invoice_item_id);

                    if (! $invItem) {
                        return false;
                    }

                    $returnedTotal = PurchaseReturnItem::where('user_id', $ownerId)
                        ->where('purchase_invoice_item_id', $r->purchase_invoice_item_id)
                        ->whereHas('purchaseReturn', fn ($q) => $q->whereIn('status', ['draft', 'posted']))
                        ->sum('quantity');

                    return (float) $returnedTotal - (float) $invItem->quantity > 0.0001;
                });

            if ($hasOver) {
                return back()->with('error', 'لا يمكن ترحيل المرتجع: توجد كميات مرتجعة تتجاوز المشتراة.');
            }

            // ✅ ثبّت الإجماليات قبل القيد
            $this->recalcReturn($return->fresh());

            $return->refresh();

            // ✅ ترحيل محاسبي أولاً (عشان لو فشل القيد ما تغيّرش الحالة)
            app(\App\Services\Accounting\PostPurchaseReturnToLedger::class)->handle(
                tenantId: $ownerId,
                return: $return,
                actorUserId: auth()->id()
            );

            // ✅ بعدها علّم المرتجع posted
            $return->update([
                'status' => $return->status === 'draft' ? 'posted' : $return->status,
                'posted_at' => now(),
                'posted_by' => auth()->id(),          // ✅ ID مش name
                'updated_by' => auth()->user()->name,
            ]);

            return back()->with('success', 'تم ترحيل مرتجع المشتريات + إنشاء قيد يومية');
        });
    }

    public function cancel($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $return = PurchaseReturn::where('user_id', $ownerId)->findOrFail($id);

            $return->update([
                'status' => 'cancelled',
                'subtotal' => 0,
                'tax_value' => 0,
                'total' => 0,
                'posted_at' => null,
                'posted_by' => null,
                'updated_by' => auth()->user()->name,
            ]);

            return back()->with('success', 'تم إلغاء مرتجع المشتريات');
        });
    }

    public function destroy($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $return = PurchaseReturn::where('user_id', $ownerId)->findOrFail($id);

            $return->items()->delete();
            $return->delete();

            return redirect()
                ->route('purchase_returns.index')
                ->with('success', 'تم حذف مرتجع المشتريات');
        });
    }

    // ==========================
    // Helpers
    // ==========================

    private function validateReturn(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            // لو عندك supplier_id في purchase_returns
            'supplier_id' => ['nullable', 'integer'],
            'purchase_invoice_id' => ['nullable', 'integer'],

            'return_number' => ['nullable', 'string', 'max:255'],
            'return_date' => ['nullable', 'date'],
            'transaction_id' => ['nullable', 'string', 'max:255'],

            'status' => ['nullable', 'in:draft,posted,cancelled'],

            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.purchase_invoice_item_id' => ['nullable', 'integer'], // ✅ مهم عشان تمنع over-return
            'items.*.warehouse_name_snapshot' => ['nullable', 'string', 'max:255'],
            'items.*.transaction_id' => ['nullable', 'string', 'max:255'],

            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],

            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        $data = $request->validate($rules);

        // ✅ لو ربطت المرتجع بفاتورة: ممكن تعمل validation business تمنع رجوع أكتر من المشتريات
        // هسيبها اختيارية هنا عشان انت لسه بتبني واحدة واحدة.

        return $data;
    }

    private function syncItems(PurchaseReturn $return, array $items, string $actor): void
    {
        $ownerId = $this->ownerId();

        foreach ($items as $row) {

            $qty = round((float) $row['quantity'], 2);
            $unit = round((float) $row['unit_price'], 2);

            $lineSub = round($qty * $unit, 2);

            $tRate = isset($row['tax_rate']) ? (float) $row['tax_rate'] : 0;
            $tValue = round($lineSub * ($tRate / 100), 2);

            $lineTotal = round($lineSub + $tValue, 2);

            PurchaseReturnItem::create([
                'user_id' => $ownerId,
                'purchase_return_id' => $return->id,
                'item_id' => $row['item_id'],

                'purchase_invoice_item_id' => $row['purchase_invoice_item_id'] ?? null,

                'warehouse_name_snapshot' => $row['warehouse_name_snapshot'] ?? null,
                'transaction_id' => $row['transaction_id'] ?? ($return->transaction_id ?? null),

                'quantity' => $qty,
                'unit_price' => $unit,

                'tax_rate' => $tRate ?: null,
                'tax_value' => $tValue,

                'line_subtotal' => $lineSub,
                'line_total' => $lineTotal,

                'notes' => $row['notes'] ?? null,
                'date' => now()->toDateString(),
                'updated_by' => $actor,
            ]);
        }
    }

    private function recalcReturn(PurchaseReturn $return): void
    {
        $return->load(['items']);

        if ($return->status === 'cancelled') {
            $return->update([
                'subtotal' => 0,
                'tax_value' => 0,
                'total' => 0,
            ]);

            return;
        }

        $sub = round((float) $return->items->sum('line_subtotal'), 2);
        $tax = round((float) $return->items->sum('tax_value'), 2);
        $tot = round($sub + $tax, 2);

        $return->update([
            'subtotal' => $sub,
            'tax_value' => $tax,
            'total' => $tot,
            'updated_by' => auth()->user()->name,
        ]);
    }

    private function nextPurchaseReturnCode(int $ownerId): string
    {
        $prefix = 'PR-';

        $last = PurchaseReturn::where('user_id', $ownerId)
            ->where('purchase_return_code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $lastNumber = 0;
        if ($last?->purchase_return_code) {
            $digits = preg_replace('/\D+/', '', $last->purchase_return_code);
            $lastNumber = (int) ($digits ?: 0);
        }

        $next = $lastNumber + 1;

        return $prefix.str_pad((string) $next, 8, '0', STR_PAD_LEFT); // PR-00000001
    }

    // ==========================
    // PDF (اختياري)
    // ==========================

    public function pdf($id)
    {
        $ownerId = $this->ownerId();

        $return = PurchaseReturn::with([
            'supplier:id,name,phone,code',
            'invoice:id,user_id,purchase_invoice_code,invoice_number,invoice_date,currency_code,exchange_rate', // لو اسمها purchaseInvoice غيّرها
            'items.item:id,name,items_code,barcode',
        ])->where('user_id', $ownerId)->findOrFail($id);

        $setting = Settings::where('user_id', $ownerId)
            ->where('status', true)
            ->first();

        // ✅ Arabic Shaping (ar-php) - نفس ستايلك
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

        // ✅ IMPORTANT: don't shape MIXED Arabic+Latin/Numbers
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.purchase_returns.pdf', [
            'return' => $return,
            'setting' => $setting,
            'shape' => $shape,
        ])->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'Cairo',
            ]);

        $code = $return->purchase_return_code ?? ('PR-'.$return->id);

        return $pdf->stream("purchase-return-{$code}.pdf");
    }

    public function createFromInvoice($invoiceId)
    {
        $ownerId = $this->ownerId();

        $invoice = PurchaseInvoice::with([
            'supplier:id,name,phone,code',
            'items.item:id,name,items_code,barcode',
        ])
            ->where('user_id', $ownerId)
            ->findOrFail($invoiceId);

        if (! in_array($invoice->status, ['posted', 'paid', 'partial'], true) || $invoice->status === 'cancelled') {
            return back()->with('error', 'لا يمكن إنشاء مرتجع إلا لفاتورة مُرحّلة');
        }

        $items = $invoice->items->map(function ($invItem) use ($ownerId) {

            $returnedQty = PurchaseReturnItem::where('user_id', $ownerId)
                ->where('purchase_invoice_item_id', $invItem->id)
                ->sum('quantity');

            $purchased = (float) $invItem->quantity;
            $available = max(0, round($purchased - (float) $returnedQty, 2));

            return [
                'purchase_invoice_item_id' => $invItem->id,
                'item_id' => $invItem->item_id,
                'item_name' => $invItem->item?->name ?? '',
                'code' => $invItem->item?->items_code ?? null,
                'barcode' => $invItem->item?->barcode ?? null,

                'qty_purchased' => round($purchased, 2),
                'qty_returned' => round((float) $returnedQty, 2),
                'qty_available' => $available,

                'unit_price' => (float) $invItem->unit_price,
                'tax_rate' => (float) ($invItem->tax_rate ?? 0),
                'warehouse_name_snapshot' => $invItem->warehouse_name_snapshot ?? null,
            ];
        })->values()->toArray();

        return view('admin.purchase_returns.create_from_invoice', compact('invoice', 'items'));
    }
}
