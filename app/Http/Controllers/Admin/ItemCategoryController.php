<?php

namespace App\Http\Controllers\Admin;

use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemCategoryController extends AdminBaseController
{
    protected function authorizeItem(ItemCategory $itemCategory): void
    {
        abort_if((int) $itemCategory->user_id !== $this->ownerId(), 403);
    }

    public function index(Request $request)
    {
        $ownerId = $this->ownerId();

        $q = ItemCategory::query()
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

        return view('admin.item_categories.index', compact('items'));
    }

    public function create()
    {
        return view('admin.item_categories.create');
    }

    public function store(Request $request)
    {
        $ownerId = $this->ownerId();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // ✅ اختياري: منع تكرار نفس الاسم داخل نفس الشركة
                Rule::unique('item_categories')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
        ]);

        ItemCategory::create([
            'user_id' => $ownerId,
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'date' => $validated['date'] ?? null,
            'status' => $request->boolean('status'),
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()
            ->route('item_categories.index')
            ->with('success', 'تم إضافة الفئة بنجاح');
    }

    public function show(ItemCategory $itemCategory)
    {
        abort(404);
    }

    public function edit(ItemCategory $itemCategory)
    {
        $this->authorizeItem($itemCategory);

        return view('admin.item_categories.edit', compact('itemCategory'));
    }

    public function update(Request $request, ItemCategory $itemCategory)
    {
        $this->authorizeItem($itemCategory);
        $ownerId = $this->ownerId();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('item_categories')
                    ->where(fn ($q) => $q->where('user_id', $ownerId))
                    ->ignore($itemCategory->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
        ]);

        $itemCategory->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'date' => $validated['date'] ?? null,
            'status' => $request->boolean('status'),
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()
            ->route('item_categories.index')
            ->with('success', 'تم تعديل الفئة بنجاح');
    }

    public function destroy(ItemCategory $itemCategory)
    {
        $this->authorizeItem($itemCategory);

        $itemCategory->delete();

        return redirect()
            ->route('item_categories.index')
            ->with('success', 'تم حذف الفئة بنجاح');
    }

    public function toggleStatus($id)
    {
        $ownerId = $this->ownerId();

        $item = ItemCategory::query()
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
