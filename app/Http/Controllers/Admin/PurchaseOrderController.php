<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\Items;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Services\Suppliers\CreatePurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends AdminBaseController
{
    public function index(): View
    {
        $orders = PurchaseOrder::query()
            ->where('user_id', $this->ownerId())
            ->with('supplier:id,name')
            ->latest('id')
            ->paginate(20);

        return view('admin.purchase_orders.index', compact('orders'));
    }

    public function create(): View
    {
        $ownerId = $this->ownerId();

        $suppliers = Supplier::query()->where('user_id', $ownerId)->orderBy('name')->get();
        $items = Items::query()->where('user_id', $ownerId)->orderBy('name')->get();

        return view('admin.purchase_orders.create', compact('suppliers', 'items'));
    }

    public function store(StorePurchaseOrderRequest $request, CreatePurchaseOrder $service): RedirectResponse
    {
        $order = $service->handle(
            tenantId: $this->ownerId(),
            actorName: auth()->user()->name ?? 'system',
            data: $request->validated(),
        );

        return redirect()->route('purchase_orders.show', $order)->with('success', 'تم إنشاء أمر الشراء بنجاح.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        abort_if((int) $purchaseOrder->user_id !== $this->ownerId(), 403);

        $purchaseOrder->load(['supplier:id,name', 'items.item:id,name']);

        return view('admin.purchase_orders.show', ['order' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        abort_if((int) $purchaseOrder->user_id !== $this->ownerId(), 403);

        return $this->show($purchaseOrder);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_if((int) $purchaseOrder->user_id !== $this->ownerId(), 403);

        $data = $request->validate([
            'status' => ['required', 'in:draft,approved,closed,cancelled'],
        ]);

        $purchaseOrder->update([
            'status' => $data['status'],
            'updated_by' => auth()->user()->name ?? 'system',
        ]);

        return back()->with('success', 'تم تحديث حالة أمر الشراء.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_if((int) $purchaseOrder->user_id !== $this->ownerId(), 403);

        $purchaseOrder->delete();

        return redirect()->route('purchase_orders.index')->with('success', 'تم حذف أمر الشراء.');
    }

    public function convertToInvoice(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_if((int) $purchaseOrder->user_id !== $this->ownerId(), 403);

        $existingInvoice = PurchaseInvoice::query()
            ->where('user_id', $this->ownerId())
            ->where('purchase_order_id', (string) $purchaseOrder->id)
            ->first();

        if ($existingInvoice) {
            return redirect()
                ->route('purchase_invoices.show', $existingInvoice->id)
                ->with('info', 'تم تحويل أمر الشراء مسبقا.');
        }

        return DB::transaction(function () use ($purchaseOrder): RedirectResponse {
            $purchaseOrder->load('items');

            if ($purchaseOrder->items->isEmpty()) {
                return back()->with('error', 'لا يمكن تحويل أمر شراء بدون أصناف.');
            }

            $invoice = PurchaseInvoice::create([
                'user_id' => $this->ownerId(),
                'supplier_id' => $purchaseOrder->supplier_id,
                'purchase_invoice_code' => $this->nextPurchaseInvoiceCode($this->ownerId()),
                'invoice_number' => $purchaseOrder->order_number,
                'invoice_date' => now()->toDateString(),
                'purchase_order_id' => (string) $purchaseOrder->id,
                'payment_type' => 'credit',
                'due_date' => now()->addDays(30)->toDateString(),
                'currency_code' => 'EGP',
                'exchange_rate' => 1,
                'tax_included' => false,
                'discount_type' => 'none',
                'discount_rate' => 0,
                'discount_value' => 0,
                'shipping_cost' => 0,
                'other_charges' => 0,
                'status' => 'draft',
                'notes' => 'Generated from PO '.$purchaseOrder->order_number,
                'date' => now()->toDateString(),
                'updated_by' => auth()->user()->name ?? 'system',
            ]);

            foreach ($purchaseOrder->items as $line) {
                $lineSubtotal = round((float) $line->quantity * (float) $line->unit_price, 2);

                PurchaseInvoiceItem::create([
                    'user_id' => $this->ownerId(),
                    'purchase_invoice_id' => $invoice->id,
                    'item_id' => $line->item_id,
                    'quantity' => round((float) $line->quantity, 2),
                    'unit_price' => round((float) $line->unit_price, 2),
                    'discount_type' => 'none',
                    'discount_rate' => null,
                    'discount_value' => 0,
                    'tax_rate' => 0,
                    'tax_value' => 0,
                    'line_subtotal' => $lineSubtotal,
                    'line_total' => $lineSubtotal,
                    'date' => now()->toDateString(),
                    'updated_by' => auth()->user()->name ?? 'system',
                ]);
            }

            $totals = $invoice->items()
                ->selectRaw('COALESCE(SUM(line_subtotal),0) as subtotal, COALESCE(SUM(line_total),0) as total')
                ->first();

            $invoice->update([
                'subtotal_before_discount' => round((float) ($totals->subtotal ?? 0), 2),
                'subtotal' => round((float) ($totals->subtotal ?? 0), 2),
                'tax_value' => 0,
                'total' => round((float) ($totals->total ?? 0), 2),
                'paid_amount' => 0,
                'remaining_amount' => round((float) ($totals->total ?? 0), 2),
            ]);

            $purchaseOrder->update([
                'status' => 'closed',
                'updated_by' => auth()->user()->name ?? 'system',
            ]);

            return redirect()
                ->route('purchase_invoices.show', $invoice->id)
                ->with('success', 'تم تحويل أمر الشراء إلى فاتورة مشتريات بنجاح.');
        });
    }

    private function nextPurchaseInvoiceCode(int $ownerId): string
    {
        $prefix = 'PI-';

        $lastInvoice = PurchaseInvoice::query()
            ->where('user_id', $ownerId)
            ->where('purchase_invoice_code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $lastNumber = 0;

        if ($lastInvoice?->purchase_invoice_code) {
            $digits = preg_replace('/\D+/', '', $lastInvoice->purchase_invoice_code);
            $lastNumber = (int) ($digits ?: 0);
        }

        return $prefix.str_pad((string) ($lastNumber + 1), 8, '0', STR_PAD_LEFT);
    }
}
