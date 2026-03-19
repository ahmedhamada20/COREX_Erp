<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\Items;
use App\Models\StockAdjustment;
use App\Models\Stores;
use App\Services\Inventory\PostStockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAdjustmentController extends AdminBaseController
{
    public function index(): View
    {
        $adjustments = StockAdjustment::query()
            ->where('user_id', $this->ownerId())
            ->withCount('lines')
            ->latest('id')
            ->paginate(20);

        return view('admin.stock_adjustments.index', compact('adjustments'));
    }

    public function create(): View
    {
        $ownerId = $this->ownerId();
        $items = Items::query()->where('user_id', $ownerId)->orderBy('name')->get();
        $stores = Stores::query()->where('user_id', $ownerId)->orderBy('name')->get();

        return view('admin.stock_adjustments.create', compact('items', 'stores'));
    }

    public function store(StoreStockAdjustmentRequest $request, PostStockAdjustment $service): RedirectResponse
    {
        $adjustment = $service->handle(
            tenantId: $this->ownerId(),
            actorName: auth()->user()->name ?? 'system',
            data: $request->validated(),
        );

        return redirect()->route('stock_adjustments.show', $adjustment)->with('success', 'تم حفظ تسوية المخزون بنجاح.');
    }

    public function show(StockAdjustment $stockAdjustment): View
    {
        abort_if((int) $stockAdjustment->user_id !== $this->ownerId(), 403);

        $stockAdjustment->load(['lines.item:id,name', 'lines.store:id,name']);

        return view('admin.stock_adjustments.show', ['adjustment' => $stockAdjustment]);
    }

    public function edit(StockAdjustment $stockAdjustment): View
    {
        return $this->show($stockAdjustment);
    }

    public function update(Request $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        abort_if((int) $stockAdjustment->user_id !== $this->ownerId(), 403);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $stockAdjustment->update([
            'notes' => $data['notes'] ?? null,
            'updated_by' => auth()->user()->name ?? 'system',
        ]);

        return back()->with('success', 'تم تحديث ملاحظات التسوية.');
    }

    public function destroy(StockAdjustment $stockAdjustment): RedirectResponse
    {
        abort_if((int) $stockAdjustment->user_id !== $this->ownerId(), 403);

        $stockAdjustment->delete();

        return redirect()->route('stock_adjustments.index')->with('success', 'تم حذف سند تسوية المخزون.');
    }
}
