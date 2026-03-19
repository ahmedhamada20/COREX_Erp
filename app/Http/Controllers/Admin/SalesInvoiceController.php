<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\SalesInvoicesDataTable;
use App\Models\Customer;
use App\Models\ItemCategory;
use App\Models\Items;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Stores;
use App\Models\Treasuries;
use App\Models\UserShift;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends AdminBaseController
{
    public function index(SalesInvoicesDataTable $dataTable)
    {
        return $dataTable->render('admin.sales_invoices.index');

    }

    public function create()
    {
        $ownerId = (int) (auth()->user()->owner_user_id ?? auth()->id());
        $userId = auth()->id();

        $openShift = UserShift::where('user_id', $ownerId) // أو $userId حسب تصميمك
            ->where('actor_user_id', $userId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $openShiftTreasuryId = $openShift?->treasury_id;
        $openShiftTreasuryName = null;

        if ($openShiftTreasuryId) {
            $openShiftTreasuryName = Treasuries::where('user_id', $ownerId)->where('id', $openShiftTreasuryId)->value('name');
        }

        $categories = ItemCategory::where('user_id', $ownerId)->orderBy('name')->get(['id', 'name']);

        // باقي الداتا
        $warehouses = Stores::where('user_id', $ownerId)->get(['id', 'name']);
        $treasuries = Treasuries::where('user_id', $ownerId)->get(['id', 'name']);

        return view('admin.sales_invoices.create', compact(
            'warehouses', 'treasuries', 'categories',
            'openShiftTreasuryId', 'openShiftTreasuryName'
        ));
    }

    public function store(Request $request)
    {
        $ownerId = $this->ownerId();
        $data = $this->validateInvoice($request);

        return DB::transaction(function () use ($data, $ownerId) {

            $actor = auth()->user()->name;

            $invoice = SalesInvoice::create([
                'user_id' => $ownerId,
                'customer_id' => $data['customer_id'],
                'invoice_number' => $data['invoice_number'] ?? $this->nextSalesInvoiceNumber($ownerId),
                'invoice_code' => $data['invoice_code'] ?? $this->nextSalesInvoiceCode($ownerId),

                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,

                'payment_type' => $data['payment_type'], // cash|credit
                'status' => $data['status'] ?? 'draft',

                // invoice-level discount (amount)
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),

                'notes' => $data['notes'] ?? null,
                'date' => now()->toDateString(),
                'updated_by' => $actor,
            ]);

            $this->syncItems($invoice, $data['items'] ?? [], $actor);
            $this->recalcInvoice($invoice);
            $this->syncPayments($invoice, $data['payment'] ?? []);

            return redirect()
                ->route('sales_invoices.show', $invoice->id)
                ->with('success', 'تم إنشاء فاتورة المبيعات بنجاح');
        });
    }

    private function syncPayments(SalesInvoice $invoice, array $payment): void
    {
        // امسح أي مدفوعات قديمة لو بتعمل update (مش store)
        // $invoice->payments()->delete();

        $treasuryId = (int) ($payment['treasury_id'] ?? 0);
        $terminalId = $payment['terminal_id'] ?? null;

        $lines = [
            'cash' => (float) ($payment['cash'] ?? 0),
            'card' => (float) ($payment['card'] ?? 0),
            'wallet' => (float) ($payment['wallet'] ?? 0),
        ];

        foreach ($lines as $method => $amount) {
            $amount = round(max(0, $amount), 4);
            if ($amount <= 0) {
                continue;
            }

            if ($treasuryId <= 0) {
                throw new \RuntimeException('treasury_id is required when payment amount > 0');
            }

            \App\Models\SalesPayment::create([
                'sales_invoice_id' => $invoice->id,
                'treasury_id' => $treasuryId,
                'terminal_id' => $terminalId ?: null,
                'method' => $method,
                'amount' => $amount,
                'payment_date' => $invoice->invoice_date,
                'reference' => null,
            ]);
        }
    }

    public function show($id)
    {
        $ownerId = $this->ownerId();

        $invoice = SalesInvoice::with([
            'customer:id,name,phone,code,account_number',
            'items.item:id,name,items_code,barcode,type',

            // ✅ returns + JE + lines + accounts + reversal (لو بتعمل reverse)
            'returns:id,sales_invoice_id,customer_id,total,subtotal,vat_amount,return_date,journal_entry_id,created_at',
            'returns.journalEntry.lines.account:id,name,account_number',
            'returns.journalEntry.reversedEntry:id,entry_number,entry_date,status,total_debit,total_credit,reversed_entry_id,posted_at,posted_by,description,source',
            'returns.journalEntry.reversedEntry.lines.account:id,name,account_number',

            // payments + treasury + payment JE + lines + accounts
            'payments.treasury:id,name,account_id',
            'payments.journalEntry.lines.account:id,name,account_number',

            // invoice posting JE + lines + accounts + reversal
            'journalEntry.lines.account:id,name,account_number',
            'journalEntry.reversedEntry:id,entry_number,entry_date,status,total_debit,total_credit,reversed_entry_id,posted_at,posted_by,description,source',
            'journalEntry.reversedEntry.lines.account:id,name,account_number',
        ])
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $journalEntries = collect();

        // 1) قيد ترحيل الفاتورة
        if ($invoice->journalEntry) {
            $journalEntries->push([
                'type' => 'posting',
                'label' => 'قيد ترحيل الفاتورة',
                'je' => $invoice->journalEntry,
            ]);

            // لو القيد الأصلي اتعمله عكس
            if (! empty($invoice->journalEntry->reversedEntry)) {
                $journalEntries->push([
                    'type' => 'reversal',
                    'label' => 'قيد عكسي (Reversal)',
                    'je' => $invoice->journalEntry->reversedEntry,
                ]);
            }
        }

        // 2) قيود التحصيل (payments)
        foreach (($invoice->payments ?? collect()) as $p) {
            if ($p->journalEntry) {
                $journalEntries->push([
                    'type' => 'receipt',
                    'label' => 'قيد تحصيل دفعة',
                    'payment' => $p,
                    'je' => $p->journalEntry,
                ]);
            }
        }

        // 3) ✅ قيود المرتجعات
        foreach (($invoice->returns ?? collect()) as $r) {
            if ($r->journalEntry) {
                $journalEntries->push([
                    'type' => 'return',
                    'label' => 'قيد مرتجع مبيعات',
                    'return' => $r,
                    'je' => $r->journalEntry,
                ]);

                // لو قيد المرتجع اتعمله عكس (عند cancel return)
                if (! empty($r->journalEntry->reversedEntry)) {
                    $journalEntries->push([
                        'type' => 'return_reversal',
                        'label' => 'قيد عكسي لمرتجع المبيعات',
                        'return' => $r,
                        'je' => $r->journalEntry->reversedEntry,
                    ]);
                }
            }
        }

        // ✅ ترتيب حسب (تاريخ القيد + id) عشان نفس اليوم يرتب صح
        $journalEntries = $journalEntries
            ->sortBy(fn ($x) => ($x['je']->entry_date ?? '0000-00-00').'-'.str_pad((string) $x['je']->id, 10, '0', STR_PAD_LEFT))
            ->values();

        return view('admin.sales_invoices.show', compact('invoice', 'journalEntries'));
    }

    public function edit($id)
    {
        $ownerId = $this->ownerId();
        $userId = auth()->id();

        $invoice = SalesInvoice::with(['items.item', 'customer', 'payments'])
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        if (in_array($invoice->status, ['posted', 'paid', 'partial'], true)) {
            return back()->with('error', 'لا يمكن تعديل فاتورة مُرحّلة. للتصحيح: أنشئ إشعار دائن/مدين أو اعمل قيد عكسي حسب الصلاحيات.');
        }

        // ✅ Shift المفتوح (نفس create)
        $openShift = UserShift::where('user_id', $ownerId)
            ->where('actor_user_id', $userId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $openShiftTreasuryId = $openShift?->treasury_id;
        $openShiftTreasuryName = null;

        if ($openShiftTreasuryId) {
            $openShiftTreasuryName = Treasuries::where('user_id', $ownerId)
                ->where('id', $openShiftTreasuryId)
                ->value('name');
        }

        // ✅ نفس create (عشان Blade)
        $categories = ItemCategory::where('user_id', $ownerId)->orderBy('name')->get(['id', 'name']);
        $warehouses = Stores::where('user_id', $ownerId)->get(['id', 'name']);
        $treasuries = Treasuries::where('user_id', $ownerId)->get(['id', 'name']);

        // terminals (لو عندك موديل Terminals)
        $terminals = class_exists(\App\Models\Terminals::class)
            ? \App\Models\Terminals::where('user_id', $ownerId)->get(['id', 'name'])
            : collect();

        $customers = Customer::where('user_id', $ownerId)->orderByDesc('id')->get();

        $itemsJs = Items::where('user_id', $ownerId)
            ->select(
                'id', 'name', 'items_code as code', 'barcode', 'type',
                'unit_id', 'retail_unit', 'does_has_retail_unit', 'retail_uom_quintToParent as factor'
            )
            ->orderByDesc('id')
            ->get()
            ->toArray();

        return view('admin.sales_invoices.edit', compact(
            'invoice', 'customers', 'itemsJs',
            'warehouses', 'treasuries', 'terminals', 'categories',
            'openShiftTreasuryId', 'openShiftTreasuryName'
        ));
    }

    public function update(Request $request, $id)
    {
        $ownerId = $this->ownerId();

        $invoice = SalesInvoice::where('user_id', $ownerId)->findOrFail($id);

        if (in_array($invoice->status, ['posted', 'paid', 'partial'], true)) {
            return back()->with('error', 'لا يمكن تعديل فاتورة مُرحّلة.');
        }

        $data = $this->validateInvoice($request, true);

        return DB::transaction(function () use ($data, $id, $ownerId) {

            $invoice = SalesInvoice::with(['payments'])
                ->where('user_id', $ownerId)
                ->lockForUpdate()
                ->findOrFail($id);

            $actor = auth()->user()->name;

            // ✅ احسب خصم الفاتورة (global discount) كـ amount ثابت يتخزن في discount_amount
            $invoiceDiscountAmount = $this->computeInvoiceDiscountAmount($data);

            $invoice->update([
                'customer_id' => $data['customer_id'],
                'invoice_number' => $data['invoice_number'] ?? $invoice->invoice_number,
                'invoice_code' => $data['invoice_code'] ?? $invoice->invoice_code,

                'invoice_date' => $data['invoice_date'] ?? $invoice->invoice_date,
                'due_date' => $data['due_date'] ?? null,

                'payment_type' => $data['payment_type'],
                'discount_amount' => $invoiceDiscountAmount,

                'notes' => $data['notes'] ?? null,
                'updated_by' => $actor,
            ]);

            // ✅ Items
            $invoice->items()->delete();
            $this->syncItems($invoice, $data['items'] ?? [], $actor);

            // ✅ Split Payments: امسح القديم واكتب الجديد
            if (method_exists($invoice, 'payments')) {
                $invoice->payments()->delete();
                $this->syncPayments($invoice, $data['payment'] ?? []);
            }

            // ✅ Recalc totals/status من DB (items + payments)
            $this->recalcInvoice($invoice->fresh());

            return redirect()
                ->route('sales_invoices.show', $invoice->id)
                ->with('success', 'تم تعديل فاتورة المبيعات بنجاح');
        });
    }

    public function destroy($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $invoice = SalesInvoice::where('user_id', $ownerId)->findOrFail($id);

            if (in_array($invoice->status, ['posted', 'paid', 'partial'], true)) {
                return back()->with('error', 'لا يمكن حذف فاتورة مُرحّلة');
            }

            $invoice->items()->delete();
            $invoice->delete();

            return redirect()
                ->route('sales_invoices.index')
                ->with('success', 'تم حذف فاتورة المبيعات');
        });
    }

    // ==========================
    // ERP Actions
    // ==========================

    public function post($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $invoice = SalesInvoice::with(['items.item', 'customer'])
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

            // ✅ ثبّت الإجماليات قبل القيد
            $this->recalcInvoice($invoice->fresh());
            $invoice->refresh();

            if (($invoice->payment_type ?? 'cash') === 'cash') {
                $sumPayments = round((float) $invoice->payments()->sum('amount'), 4);
                if ($sumPayments > 0.0001 && abs($sumPayments - (float) $invoice->total) > 0.0001) {
                    return back()->with('error', 'مجموع المدفوعات (Split) لازم يساوي إجمالي الفاتورة قبل الترحيل.');
                }
            }

            // ✅ محاسبة (قيد مبيعات + VAT Output + (COGS/Inventory لو Stock))
            app(\App\Services\Accounting\PostSalesInvoiceToLedger::class)->handle(
                tenantId: $ownerId,
                invoice: $invoice->fresh(),
                actorUserId: auth()->id()
            );

            // ✅ حالة الفاتورة
            $invoice->update([
                'status' => $invoice->status === 'draft' ? 'posted' : $invoice->status,
                'posted_at' => now(),
                'posted_by' => auth()->user()->name,
                'updated_by' => auth()->user()->name,
            ]);

            // ✅ كاش: خليها مدفوعة بالكامل بمجرد الترحيل (نفس purchase)
            $this->recalcInvoice($invoice->fresh());

            return back()->with('success', 'تم ترحيل فاتورة المبيعات + إنشاء قيد يومية');
        });
    }

    public function cancel($id)
    {
        $ownerId = $this->ownerId();

        return DB::transaction(function () use ($id, $ownerId) {

            $invoice = SalesInvoice::where('user_id', $ownerId)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($invoice->status === 'cancelled') {
                return back()->with('info', 'الفاتورة ملغاة بالفعل');
            }

            // ✅ لو مترحّلة: اعمل reversal للقيد
            if (! empty($invoice->journal_entry_id)) {
                app(\App\Services\Accounting\ReverseJournalEntry::class)->handle(
                    tenantId: $ownerId,
                    entryId: (int) $invoice->journal_entry_id,
                    actorUserId: auth()->id(),
                    reason: "Cancel Sales Invoice {$invoice->invoice_code}"
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

            $this->recalcInvoice($invoice);

            return back()->with('success', 'تم إلغاء فاتورة المبيعات');
        });
    }

    // ==========================
    // Helpers
    // ==========================

    private function validateInvoice(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'customer_id' => ['required', 'integer'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'invoice_code' => ['nullable', 'string', 'max:255'],

            'invoice_date' => ['required', 'date'],
            'payment_type' => ['required', 'in:cash,credit'],
            'due_date' => ['nullable', 'date'],

            // Totals coming from UI (still recalculated on server)
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'vat_amount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'remaining_amount' => ['nullable', 'numeric', 'min:0'],
            'global_discount_type' => ['nullable', 'in:amount,percent'],
            'global_discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],

            // POS lines
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'items.*.price' => ['required', 'numeric', 'min:0'],

            // POS discount/vat (your UI sends rate + type/value)
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:amount,percent'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0'],

            // optional snapshot
            'items.*.cost_price' => ['nullable', 'numeric', 'min:0'],

            // split payment
            'payment' => ['nullable', 'array'],
            'payment.mode' => ['nullable', 'in:split'],
            'payment.cash' => ['nullable', 'numeric', 'min:0'],
            'payment.card' => ['nullable', 'numeric', 'min:0'],
            'payment.wallet' => ['nullable', 'numeric', 'min:0'],
            'payment.treasury_id' => ['nullable', 'integer'],
            'payment.terminal_id' => ['nullable', 'integer'],
        ];

        $data = $request->validate($rules);

        // Credit invoices => must have due_date
        if (($data['payment_type'] ?? 'credit') === 'cash') {
            $data['due_date'] = null;
        } else {
            if (empty($data['due_date'])) {
                $data['due_date'] = now()->addDays(30)->toDateString();
            }
        }

        return $data;
    }

    private function syncItems(SalesInvoice $invoice, array $items, string $actor): void
    {
        foreach ($items as $row) {

            $qty = round((float) ($row['qty'] ?? 0), 4);
            $price = round((float) ($row['price'] ?? 0), 4);

            if ($qty <= 0) {
                continue;
            }

            $gross = round($qty * $price, 4);

            $discVal = round((float) ($row['discount_value'] ?? 0), 4);
            $discType = (string) ($row['discount_type'] ?? 'amount');

            $discount = 0.0;
            if ($discType === 'percent') {
                $discount = round($gross * min(100, $discVal) / 100, 4);
            } else {
                $discount = $discVal;
            }
            $discount = min($discount, $gross);

            $net = max(0, round($gross - $discount, 4));

            $vatRate = max(0, (float) ($row['vat_rate'] ?? 0));
            $vat = round($net * ($vatRate / 100), 4);

            $lineTotal = round($net + $vat, 4);

            SalesInvoiceItem::create([
                'sales_invoice_id' => $invoice->id,
                'item_id' => (int) $row['item_id'],
                'quantity' => $qty,
                'price' => $price,
                'discount' => $discount,
                'vat' => $vat,
                'total' => $lineTotal,
                'cost_price' => round((float) ($row['cost_price'] ?? 0), 4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function recalcInvoice(SalesInvoice $invoice): void
    {
        $invoice->load(['items', 'payments']);

        if ($invoice->status === 'cancelled') {
            $invoice->update([
                'subtotal' => 0,
                'vat_amount' => 0,
                'total' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
            ]);

            return;
        }

        // 1) Net before invoice discount (after line discount)
        $lines = [];
        $netBeforeInvDisc = 0.0;

        foreach ($invoice->items as $line) {
            $qty = (float) $line->quantity;
            $price = (float) $line->price;
            $disc = (float) $line->discount;

            $lineNet = max(0, round(($qty * $price) - $disc, 4));
            $lineVat = max(0, round((float) $line->vat, 4));

            $rate = 0.0;
            if ($lineNet > 0.0001 && $lineVat > 0) {
                $rate = $lineVat / $lineNet; // effective VAT rate
            }

            $lines[] = [
                'net' => $lineNet,
                'rate' => $rate,
            ];

            $netBeforeInvDisc += $lineNet;
        }

        $netBeforeInvDisc = round($netBeforeInvDisc, 4);

        $invDiscount = round((float) ($invoice->discount_amount ?? 0), 4);
        $invDiscount = min($invDiscount, $netBeforeInvDisc);

        // 2) Allocate invoice discount on lines proportionally and recompute VAT
        $vat = 0.0;
        $taxBase = 0.0;

        foreach ($lines as $l) {
            $w = ($netBeforeInvDisc > 0.0001) ? ($l['net'] / $netBeforeInvDisc) : 0.0;
            $alloc = round($invDiscount * $w, 4);
            $adjNet = max(0, round($l['net'] - $alloc, 4));

            $taxBase += $adjNet;
            $vat += round($adjNet * $l['rate'], 4);
        }

        $taxBase = round($taxBase, 4);
        $vat = round($vat, 4);
        $subtotal = $taxBase;            // ✅ subtotal = taxable base after all discounts
        $total = round($subtotal + $vat, 4);

        // 3) paid from payments (source of truth)
        $paid = round((float) $invoice->payments->sum('amount'), 4);

        // cash invoices: ممنوع نخليها auto paid إلا لو مفيش payments table أصلاً
        // (هتتعامل مع ده في post() / syncPayments)
        if ($invoice->payment_type === 'cash' && $paid <= 0.0001 && in_array($invoice->status, ['posted', 'paid', 'partial'], true)) {
            // لو POS كاش ومش بتخزن payments: اعتبرها مدفوعة
            $paid = $total;
        }

        $remaining = max(0, round($total - $paid, 4));

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
            'subtotal' => $subtotal,
            'vat_amount' => $vat,
            'total' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $status,
            'updated_by' => auth()->user()->name,
        ]);
    }

    private function nextSalesInvoiceCode(int $ownerId): string
    {
        $prefix = 'SI-';

        $last = SalesInvoice::where('user_id', $ownerId)
            ->where('invoice_code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $lastNumber = 0;
        if ($last?->invoice_code) {
            $digits = preg_replace('/\D+/', '', $last->invoice_code);
            $lastNumber = (int) ($digits ?: 0);
        }

        $next = $lastNumber + 1;

        return $prefix.str_pad((string) $next, 8, '0', STR_PAD_LEFT);
    }

    private function nextSalesInvoiceNumber(int $ownerId): string
    {
        // ممكن تخليه نفس code أو رقم خارجي
        return $this->nextSalesInvoiceCode($ownerId);
    }

    // ==========================
    // PDF (اختياري - نفس ستايل purchase)
    // ==========================
    public function pdf($id)
    {
        $ownerId = $this->ownerId();

        $invoice = \App\Models\SalesInvoice::with([
            'customer:id,name,phone,code',
            'items.item:id,name,items_code,barcode,type',
            'payments.treasury:id,name',
            'returns:id,sales_invoice_id,total,return_date',
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

        $pdf = Pdf::loadView('admin.sales_invoices.pdf', [
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

        return $pdf->stream("sales-invoice-{$invoice->invoice_number}.pdf");
    }

    private function computeInvoiceDiscountAmount(array $data): float
    {
        $type = $data['global_discount_type'] ?? 'amount';
        $value = (float) ($data['global_discount_value'] ?? 0);

        $netLines = 0.0;

        foreach (($data['items'] ?? []) as $row) {
            $qty = (float) ($row['qty'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $gross = $qty * $price;

            $discVal = (float) ($row['discount_value'] ?? 0);
            $discType = (string) ($row['discount_type'] ?? 'amount');

            $lineDisc = 0.0;
            if ($discType === 'percent') {
                $lineDisc = $gross * min(100, $discVal) / 100;
            } else {
                $lineDisc = $discVal;
            }

            $lineDisc = min($lineDisc, $gross);
            $netLines += max(0, $gross - $lineDisc);
        }

        $netLines = round($netLines, 4);

        $invDisc = 0.0;
        if ($type === 'percent') {
            $invDisc = $netLines * min(100, $value) / 100;
        } else {
            $invDisc = $value;
        }

        return round(min($invDisc, $netLines), 4);
    }
}
