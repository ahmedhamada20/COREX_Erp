<?php

namespace App\Http\Controllers\Admin;

use App\Models\SupplierCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierCategoryController extends AdminBaseController
{
    public function index(Request $request)
    {
        $ownerId = $this->ownerId();

        $q = SupplierCategory::query()
            ->where('user_id', $ownerId)
            ->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $q->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $q->where('status', (int) $request->status);
        }

        $items = $q->paginate(15)->withQueryString();

        return view('admin.supplier_categories.index', compact('items'));
    }

    public function create()
    {
        return view('admin.supplier_categories.create');
    }

    public function store(Request $request)
    {
        $ownerId = $this->ownerId();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('supplier_categories')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
        ]);

        SupplierCategory::create([
            'user_id' => $ownerId,
            'name' => $validated['name'],
            'date' => $validated['date'] ?? null,
            'status' => $request->boolean('status'),
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()
            ->route('supplier_categories.index')
            ->with('success', 'تم إضافة فئة الموردين بنجاح');
    }

    public function show(string $id)
    {
        $ownerId = $this->ownerId();

        $item = SupplierCategory::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        return view('admin.supplier_categories.show', compact('item'));
    }

    public function edit(string $id)
    {
        $ownerId = $this->ownerId();

        $item = SupplierCategory::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        return view('admin.supplier_categories.edit', compact('item'));
    }

    public function update(Request $request, string $id)
    {
        $ownerId = $this->ownerId();

        $item = SupplierCategory::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('supplier_categories')
                    ->where(fn ($q) => $q->where('user_id', $ownerId))
                    ->ignore($item->id),
            ],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
        ]);

        $item->update([
            'name' => $validated['name'],
            'date' => $validated['date'] ?? null,
            'status' => $request->boolean('status'),
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()
            ->route('supplier_categories.index')
            ->with('success', 'تم تعديل فئة الموردين بنجاح');
    }

    public function destroy(string $id)
    {
        $ownerId = $this->ownerId();

        $item = SupplierCategory::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $item->delete();

        return redirect()
            ->route('supplier_categories.index')
            ->with('success', 'تم حذف فئة الموردين بنجاح');
    }

    public function toggleStatus($id)
    {
        $ownerId = $this->ownerId();

        $item = SupplierCategory::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $item->status = ! $item->status;
        $item->updated_by = auth()->user()->name ?? (string) auth()->id();
        $item->save();

        return response()->json([
            'ok' => true,
            'status' => (int) $item->status,
            'status_text' => $item->status ? 'نشط' : 'غير نشط',
        ]);
    }
}
