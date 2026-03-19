<?php

namespace App\Http\Controllers\Admin;

use App\Models\Units;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends AdminBaseController
{
    private function authorizeUnit(Units $unit): void
    {
        abort_if((int) $unit->user_id !== $this->ownerId(), 403);
    }

    public function index(Request $request)
    {
        $ownerId = $this->ownerId();

        $q = Units::query()
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

        return view('admin.units.index', compact('items'));
    }

    public function create()
    {
        $ownerId = $this->ownerId();

        $hasMaster = Units::query()
            ->where('user_id', $ownerId)
            ->where('is_master', 1)
            ->exists();

        return view('admin.units.create', compact('hasMaster'));
    }

    public function store(Request $request)
    {
        $ownerId = $this->ownerId();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                // اختياري: منع تكرار الاسم داخل نفس الشركة
                Rule::unique('units')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
            'is_master' => ['nullable', 'boolean'],
        ]);

        $isMaster = $request->boolean('is_master');

        // ✅ لو هي رئيسية: امنع وجود رئيسية أخرى داخل نفس الشركة
        if ($isMaster) {
            $exists = Units::query()
                ->where('user_id', $ownerId)
                ->where('is_master', 1)
                ->exists();

            if ($exists) {
                return back()
                    ->withInput()
                    ->with('error', 'لا يمكن إضافة وحدة رئيسية جديدة، يوجد وحدة رئيسية بالفعل');
            }
        }

        Units::create([
            'user_id' => $ownerId,
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'date' => $validated['date'] ?? null,
            'status' => $request->boolean('status'),
            'is_master' => $isMaster,
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()
            ->route('units.index')
            ->with('success', 'تم إضافة الوحدة بنجاح');
    }

    public function show(Units $unit)
    {
        $this->authorizeUnit($unit);

        return view('admin.units.show', compact('unit'));
    }

    public function edit(Units $unit)
    {
        $this->authorizeUnit($unit);
        $ownerId = $this->ownerId();

        $hasMaster = Units::query()
            ->where('user_id', $ownerId)
            ->where('is_master', 1)
            ->where('id', '!=', $unit->id)
            ->exists();

        return view('admin.units.edit', compact('unit', 'hasMaster'));
    }

    public function update(Request $request, Units $unit)
    {
        $this->authorizeUnit($unit);
        $ownerId = $this->ownerId();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('units')
                    ->where(fn ($q) => $q->where('user_id', $ownerId))
                    ->ignore($unit->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
            'is_master' => ['nullable', 'boolean'],
        ]);

        $isMaster = $request->boolean('is_master');

        // ✅ منع تعيينها رئيسية لو فيه رئيسية غيرها
        if ($isMaster && ! $unit->is_master) {
            $exists = Units::query()
                ->where('user_id', $ownerId)
                ->where('is_master', 1)
                ->where('id', '!=', $unit->id)
                ->exists();

            if ($exists) {
                return back()
                    ->withInput()
                    ->with('error', 'لا يمكن تعيين هذه الوحدة كوحدة رئيسية لأن هناك وحدة رئيسية أخرى بالفعل');
            }
        }

        $unit->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'date' => $validated['date'] ?? null,
            'status' => $request->boolean('status'),
            'is_master' => $isMaster,
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
        ]);

        return redirect()
            ->route('units.index')
            ->with('success', 'تم تحديث الوحدة بنجاح');
    }

    public function destroy(Units $unit)
    {
        $this->authorizeUnit($unit);

        if ((bool) $unit->is_master) {
            return back()->with('error', 'لا يمكن حذف الوحدة الرئيسية');
        }

        $unit->delete();

        return redirect()
            ->route('units.index')
            ->with('success', 'تم حذف الوحدة بنجاح');
    }

    public function toggleStatus($id)
    {
        $ownerId = $this->ownerId();

        $item = Units::query()
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
