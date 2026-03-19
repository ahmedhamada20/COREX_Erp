<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\SuppliersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Services\Suppliers\CreateSupplierWithAccount;
use App\Services\Suppliers\UpdateSupplierWithAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuppliersController extends Controller
{
    public function __construct()
    {
        // $this->authorizeResource(Supplier::class, 'supplier');
    }

    private function tenantId(): int
    {
        $u = auth()->user();

        return (int) ($u->owner_user_id ?? $u->id);
    }

    public function suppliersSelect2(Request $request)
    {
        $ownerId = $this->tenantId();

        $term = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $query = Supplier::query()
            ->where('user_id', $ownerId)
            ->select('id', 'name', 'phone', 'code')
            ->orderBy('name');

        if ($term !== '') {
            $query->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $results = $paginator->getCollection()->map(function ($s) {
            $label = $s->name;
            if (! empty($s->phone)) {
                $label .= " — {$s->phone}";
            }
            if (! empty($s->code)) {
                $label .= " — {$s->code}";
            }

            return [
                'id' => $s->id,
                'text' => $label,
            ];
        })->values();

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    private function tenantActorName(): string
    {
        return (string) (auth()->user()->name ?? auth()->id());
    }

    private function findTenantSupplierOrFail(int $id): Supplier
    {
        return Supplier::query()
            ->where('user_id', $this->tenantId())
            ->findOrFail($id);
    }

    public function index(SuppliersDataTable $dataTable)
    {
        $tenantId = auth()->user()->owner_user_id ?? auth()->id();

        $categories = SupplierCategory::where('user_id', $tenantId)
            ->orderBy('name')
            ->get();

        return $dataTable->render('admin.suppliers.index', compact('categories'));
    }

    public function create()
    {
        $tenantId = auth()->user()->owner_user_id ?? auth()->id();
        $categories = SupplierCategory::where('user_id', $tenantId)->where('status', 1)->orderBy('name')->get();

        return view('admin.suppliers.create', compact('categories'));
    }

    public function store(StoreSupplierRequest $request, CreateSupplierWithAccount $action)
    {
        $tenantId = $this->tenantId();

        DB::transaction(function () use ($request, $action, $tenantId) {
            $action->handle(
                tenantId: $tenantId,
                payload: $request->validated(),
                actorName: $this->tenantActorName(),
                imageFile: $request->file('image')
            );
        });

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'تم إضافة المورد وإنشاء حسابه المالي بنجاح');
    }

    public function show($id)
    {
        $tenantId = $this->tenantId();

        $supplier = Supplier::with('supplierCategory:id,name')
            ->where('user_id', $tenantId)
            ->findOrFail($id);

        $account = null;
        $balances = collect();
        $lines = collect();
        $movements = collect();

        // KPIs للحركات المعروضة (آخر 50 حركة)
        $kpis = [
            'total_debit' => 0.0,
            'total_credit' => 0.0,
            'net_move' => 0.0, // debit - credit
        ];

        // ✅ ربط الحساب من account_number
        if (! empty($supplier->account_number)) {

            $account = Account::with(['type', 'parent'])
                ->where('user_id', $tenantId)
                ->where('account_number', $supplier->account_number)
                ->first();

            if ($account) {

                // ✅ أرصدة الحساب حسب العملة/الفرع
                $balances = \App\Models\AccountBalance::query()
                    ->where('user_id', $tenantId)
                    ->where('account_id', $account->id)
                    ->orderBy('currency_code')
                    ->orderBy('branch_id')
                    ->get();

                // ✅ الرصيد الحالي الحقيقي (اختيار EGP + branch null إن أمكن)
                $baseBalanceRow = $balances
                    ->firstWhere('currency_code', 'EGP') ?? $balances->first();

                $currentBalance = (float) ($baseBalanceRow->balance ?? 0);

                // ✅ آخر 50 سطر حركة على حساب المورد (desc)
                $linesDesc = \App\Models\JournalEntryLine::query()
                    ->with([
                        'journalEntry:id,user_id,entry_number,entry_date,source,reference_type,reference_id,description,status',
                    ])
                    ->where('user_id', $tenantId)
                    ->where('account_id', $account->id)
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get();

                // KPIs من نفس الحركات المعروضة
                $kpis['total_debit'] = (float) $linesDesc->sum('debit');
                $kpis['total_credit'] = (float) $linesDesc->sum('credit');
                $kpis['net_move'] = (float) ($kpis['total_debit'] - $kpis['total_credit']);

                /**
                 * ✅ حساب balance_after محاسبيًا:
                 * نفترض balance الحالي (بعد كل القيود).
                 * بنمشي على الحركات desc:
                 * - balance_after = الرصيد بعد هذه الحركة (الحالي الآن)
                 * - ثم نرجّع الرصيد للخلف بإزالة أثر الحركة
                 *
                 * لو حساب المورد طبيعته دائن، كثير بيحسبوا الرصيد = credit - debit
                 * لكن إحنا هنا بنستخدم balance المخزن من account_balances كما هو،
                 * ونرجّع للخلف بنفس منطق: balanceBefore = balanceAfter - (debit - credit)
                 */
                $running = $currentBalance;

                $movementsDesc = $linesDesc->map(function ($line) use (&$running) {

                    $debit = (float) ($line->debit ?? 0);
                    $credit = (float) ($line->credit ?? 0);

                    $je = $line->journalEntry;

                    $balanceAfter = $running;

                    // نرجّع خطوة للخلف
                    $running = $running - ($debit - $credit);

                    return (object) [
                        'date' => $je?->entry_date ? \Carbon\Carbon::parse($je->entry_date)->format('Y-m-d') : '-',
                        'type' => $je?->source ?? '-',
                        'ref' => $je?->entry_number ?? ('JE#'.$line->journal_entry_id),
                        'description' => $line->memo ?: ($je?->description ?? '-'),
                        'status' => $je?->status ?? '-',
                        'currency' => $line->currency_code ?? ($baseBalanceRow->currency_code ?? 'EGP'),
                        'debit' => $debit,
                        'credit' => $credit,
                        'balance_after' => $balanceAfter,
                        'line_no' => (int) ($line->line_no ?? 1),
                        'entry_id' => $line->journal_entry_id,
                    ];
                });

                $movements = $movementsDesc->reverse()->values();

                $lines = $linesDesc->reverse()->values();
            }
        }

        return view('admin.suppliers.show', compact(
            'supplier', 'account', 'balances', 'lines', 'kpis', 'movements'
        ));
    }

    public function edit(int $id)
    {
        $supplier = $this->findTenantSupplierOrFail($id);
        $tenantId = $this->tenantId();
        $categories = SupplierCategory::where('user_id', $tenantId)->where('status', 1)->orderBy('name')->get();

        return view('admin.suppliers.edit', compact('supplier', 'categories'));
    }

    public function update(UpdateSupplierRequest $request, int $id, UpdateSupplierWithAccount $action)
    {
        $tenantId = $this->tenantId();
        $supplier = $this->findTenantSupplierOrFail($id);

        DB::transaction(function () use ($request, $action, $tenantId, $supplier) {
            $action->handle(
                tenantId: $tenantId,
                supplier: $supplier,
                payload: $request->validated(),
                actorName: $this->tenantActorName(),
                imageFile: $request->file('image')
            );
        });

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'تم تعديل المورد وتحديث حسابه المالي بنجاح');
    }

    public function destroy(int $id)
    {
        $supplier = $this->findTenantSupplierOrFail($id);
        $tenantId = $this->tenantId();

        DB::transaction(function () use ($supplier, $tenantId) {

            if (! empty($supplier->account_number)) {
                Account::query()
                    ->where('user_id', $tenantId)
                    ->where('account_number', $supplier->account_number)
                    ->delete();
            }

            if ($supplier->image && Storage::disk('public')->exists($supplier->image)) {
                Storage::disk('public')->delete($supplier->image);
            }

            $supplier->delete();
        });

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'تم حذف المورد وحسابه المالي بنجاح');
    }

    public function toggleStatus(int $id)
    {
        $supplier = $this->findTenantSupplierOrFail($id);

        $tenantId = $this->tenantId();
        $actor = $this->tenantActorName();

        if (empty($supplier->account_number)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير الحالة: المورد غير مرتبط برقم حساب.',
            ], 422);
        }

        $account = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', $supplier->account_number)
            ->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير الحالة: لا يوجد حساب مرتبط بهذا المورد في شجرة الحسابات.',
            ], 422);
        }

        // لو هيقفل: تأكد إن الرصيد = 0 ومفيش أبناء نشطين
        if ($supplier->status === true) {
            $balance = (float) ($account->current_balance ?? 0);

            if (abs($balance) > 0.00001) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إيقاف المورد لأن رصيد حسابه الحالي ليس صفرًا.',
                ], 422);
            }

            if ($account->children()->where('status', true)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إيقاف المورد لأن لديه حسابات فرعية نشطة.',
                ], 422);
            }
        }

        $newStatus = ! $supplier->status;

        DB::transaction(function () use ($supplier, $account, $newStatus, $actor) {
            $supplier->update([
                'status' => $newStatus,
                'updated_by' => $actor,
            ]);

            $account->update([
                'status' => $newStatus,
                'updated_by' => $actor,
            ]);
        });

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus ? 'تم تفعيل المورد' : 'تم إيقاف المورد',
        ]);
    }
}
