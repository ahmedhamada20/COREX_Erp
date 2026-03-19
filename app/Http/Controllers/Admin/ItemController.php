<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ItemsDataTable;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\ItemCategory;
use App\Models\Items;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends AdminBaseController
{
    private function authorizeOwnerItem(Items $item): void
    {
        abort_if((int) $item->user_id !== $this->ownerId(), 403);
    }

    public function index(ItemsDataTable $dataTable)
    {
        return $dataTable->render('admin.items.index');
    }

    public function create()
    {
        $ownerId = $this->ownerId();

        $categories = ItemCategory::where('user_id', $ownerId)->latest()->get();
        $parents = Items::where('user_id', $ownerId)->whereNull('item_id')->latest()->get();

        $nextCode = Items::generateNextCode($ownerId);

        return view('admin.items.create', compact('categories', 'parents', 'nextCode'));
    }

    public function select2(\Illuminate\Http\Request $request)
    {
        $u = auth()->user();
        $ownerId = (int) ($u->owner_user_id ?? $u->id);

        $q = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 10;

        $query = \App\Models\Items::query()
            ->where('status', '1')
            ->where('user_id', $ownerId);

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                    ->orWhere('items_code', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%");
            });
        }

        $paginator = $query
            ->select([
                'id',
                'name',
                'items_code',
                'barcode',

                // ✅ الأسعار الموجودة عندك
                'price',
                'price_retail',
                'gomla_price_retail',
            ])
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        $results = $paginator->getCollection()->map(function ($it) {

            // ✅ اختار سعر افتراضي للبيع:
            // 1) price_retail (قطاعي) لو موجود
            // 2) price
            // 3) gomla_price_retail
            $defaultPrice =
                (is_numeric($it->price_retail) && (float) $it->price_retail > 0) ? (float) $it->price_retail :
                    ((is_numeric($it->price) && (float) $it->price > 0) ? (float) $it->price : (float) ($it->gomla_price_retail ?? 0));

            $parts = array_filter([
                $it->name,
                $it->items_code ? "#{$it->items_code}" : null,
                $it->barcode ? "🔎 {$it->barcode}" : null,
            ]);

            return [
                'id' => (string) $it->id,
                'text' => implode(' - ', $parts),

                // ✅ fields for UI auto-fill
                'price' => $defaultPrice,

                // اختياري: لو حبيت تغيّر السعر حسب نوع العميل (قطاعي/جملة)
                'prices' => [
                    'base' => (float) ($it->price ?? 0),
                    'retail' => (float) ($it->price_retail ?? 0),
                    'wholesale' => (float) ($it->gomla_price_retail ?? 0),
                ],
            ];
        })->values();

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function store(StoreItemRequest $request)
    {
        $ownerId = $this->ownerId();

        $data = $request->validated();
        $data['user_id'] = $ownerId;
        $data['updated_by'] = auth()->user()->name ?? (string) auth()->id();

        // ✅ السيستم يولد الكود (زي ما انت عامل)
        unset($data['items_code']);

        $imagePath = $request->file('image')?->store('items', 'public');
        $data['image'] = $imagePath;

        Items::create($data);

        return redirect()->route('items.index')
            ->with('success', 'تم إضافة الصنف بنجاح');
    }

    public function edit(Items $item)
    {
        $this->authorizeOwnerItem($item);
        $ownerId = $this->ownerId();

        $categories = ItemCategory::where('user_id', $ownerId)->latest()->get();

        $parents = Items::where('user_id', $ownerId)
            ->whereNull('item_id')
            ->where('id', '!=', $item->id)
            ->latest()
            ->get();

        return view('admin.items.edit', compact('item', 'categories', 'parents'));
    }

    public function update(UpdateItemRequest $request, Items $item)
    {
        $this->authorizeOwnerItem($item);
        $ownerId = $this->ownerId();

        $data = $request->validated();
        $data['updated_by'] = auth()->user()->name ?? (string) auth()->id();
        $data['status'] = $request->boolean('status');
        $data['does_has_retail_unit'] = $request->boolean('does_has_retail_unit');

        // ✅ تأكيد إن parent المختار تبع نفس الشركة
        if (! empty($data['item_id'])) {
            $ok = Items::where('user_id', $ownerId)->whereNull('item_id')->where('id', (int) $data['item_id'])->exists();
            abort_if(! $ok, 403);
        }

        // ✅ تأكيد إن category تبع نفس الشركة
        if (! empty($data['item_category_id'])) {
            $ok = ItemCategory::where('user_id', $ownerId)->where('id', (int) $data['item_category_id'])->exists();
            abort_if(! $ok, 403);
        }

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('items', 'public');
            if ($item->image && Storage::disk('public')->exists($item->image)) {
                Storage::disk('public')->delete($item->image);
            }
            $data['image'] = $newPath;
        } else {
            unset($data['image']);
        }

        if (! $data['does_has_retail_unit']) {
            $data['retail_unit'] = null;
            $data['retail_uom_quintToParent'] = null;
        }

        $item->update($data);

        return redirect()
            ->route('items.index')
            ->with('success', 'تم تعديل الصنف بنجاح');
    }

    public function show(Items $item)
    {
        $this->authorizeOwnerItem($item);

        $item->load(['category', 'parent']);

        return view('admin.items.show', compact('item'));
    }

    public function destroy(Items $item)
    {
        $this->authorizeOwnerItem($item);

        $item->delete();

        return redirect()->route('items.index')->with('success', 'تم حذف الصنف بنجاح');
    }

    public function toggleStatus($id)
    {
        $ownerId = $this->ownerId();

        $item = Items::query()
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

    public function ajaxStoreCategory(Request $request)
    {
        $ownerId = $this->ownerId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // ✅ منع تكرار الاسم داخل نفس الشركة (اختياري)
        $exists = ItemCategory::where('user_id', $ownerId)->where('name', $data['name'])->exists();
        if ($exists) {
            $cat = ItemCategory::where('user_id', $ownerId)->where('name', $data['name'])->first();

            return response()->json([
                'status' => true,
                'id' => $cat->id,
                'text' => $cat->name,
            ]);
        }

        $categoryId = DB::table('item_categories')->insertGetId([
            'user_id' => $ownerId,
            'name' => $data['name'],
            'status' => 1,
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'id' => $categoryId,
            'text' => $data['name'],
        ]);
    }

    public function ajaxStoreParent(Request $request)
    {
        $ownerId = $this->ownerId();

        $data = $request->validate([

            'name' => ['required', 'string', 'max:255'],
            'barcode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('items', 'barcode')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
        ]);

        $itemsCode = Items::generateNextCode($ownerId);

        $barcode = $data['barcode'] ?? null;
        if (empty($barcode)) {
            do {
                $barcode = '20'.random_int(1000000000, 9999999999); // 12 رقم
            } while (DB::table('items')->where('user_id', $ownerId)->where('barcode', $barcode)->exists());
        }

        $id = DB::table('items')->insertGetId([
            'user_id' => $ownerId,
            'items_code' => $itemsCode,
            'barcode' => $barcode,
            'name' => $data['name'],
            'status' => 1,
            'updated_by' => auth()->user()->name ?? (string) auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'id' => $id,
            'barcode' => $barcode,
            'text' => $data['name'].' ('.$itemsCode.')',
        ]);
    }
}
