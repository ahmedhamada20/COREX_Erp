<?php

namespace App\Http\Controllers\Admin;

use App\Models\Stores;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoresController extends AdminBaseController
{
    public function index(Request $request)
    {
        $ownerId = $this->ownerId();

        $q = Stores::query()
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

        return view('admin.stores.index', compact('items'));
    }

    public function create()
    {
        return view('admin.stores.create');
    }

    public function store(Request $request)
    {
        $ownerId = $this->ownerId();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                // اختياري: منع تكرار الاسم داخل نفس الشركة
                Rule::unique('stores')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
        ]);

        Stores::create([
            'user_id' => $ownerId,
            'name' => $validated['name'],
            'date' => $validated['date'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $request->boolean('status'),
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()
            ->route('stores.index')
            ->with('success', 'تم إضافة المخزن بنجاح');
    }

    public function show(string $id)
    {
        $ownerId = $this->ownerId();

        $item = Stores::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        return view('admin.stores.show', compact('item'));
    }

    public function edit(string $id)
    {
        $ownerId = $this->ownerId();

        $item = Stores::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        return view('admin.stores.edit', compact('item'));
    }

    public function update(Request $request, string $id)
    {
        $ownerId = $this->ownerId();

        $item = Stores::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('stores')
                    ->where(fn ($q) => $q->where('user_id', $ownerId))
                    ->ignore($item->id),
            ],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
        ]);

        $item->update([
            'name' => $validated['name'],
            'date' => $validated['date'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $request->boolean('status'),
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()
            ->route('stores.index')
            ->with('success', 'تم تعديل المخزن بنجاح');
    }

    public function destroy(string $id)
    {
        $ownerId = $this->ownerId();

        $item = Stores::query()
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        $item->delete();

        return redirect()
            ->route('stores.index')
            ->with('success', 'تم حذف المخزن بنجاح');
    }

    public function toggleStatus($id)
    {
        $ownerId = $this->ownerId();

        $item = Stores::query()
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
