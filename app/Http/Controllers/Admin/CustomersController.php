<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\CustomersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Account;
use App\Models\Customer;
use App\Services\Customers\CreateCustomerWithAccount;
use App\Services\Customers\UpdateCustomerWithAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CustomersController extends Controller
{
    public function __construct()
    {
        // لو عندك Policies
        // $this->authorizeResource(Customer::class, 'customer');
    }

    private function tenantId(): int
    {
        $u = auth()->user();

        return (int) ($u->owner_user_id ?? $u->id);
    }

    private function tenantActorName(): string
    {
        return (string) (auth()->user()->name ?? auth()->id());
    }

    private function findTenantCustomerOrFail(int $id): Customer
    {
        return Customer::query()
            ->where('user_id', $this->tenantId())
            ->findOrFail($id);
    }

    public function index(CustomersDataTable $dataTable)
    {
        return $dataTable->render('admin.customers.index');
    }

    public function select2(\Illuminate\Http\Request $request)
    {
        $u = auth()->user();
        $ownerId = (int) ($u->owner_user_id ?? $u->id);

        $q = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 10;

        $query = \App\Models\Customer::query()
            ->where('user_id', $ownerId);

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }

        $paginator = $query
            ->select(['id', 'name', 'phone', 'code'])
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        $results = $paginator->getCollection()->map(function ($c) {
            $parts = array_filter([
                $c->name,
                $c->phone ? "☎ {$c->phone}" : null,
                $c->code ? "#{$c->code}" : null,
            ]);

            return [
                'id' => (string) $c->id,
                'text' => implode(' - ', $parts),
            ];
        })->values();

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(StoreCustomerRequest $request, CreateCustomerWithAccount $action)
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
            ->route('customers.index')
            ->with('success', 'تم إضافة العميل وإنشاء حسابه المالي بنجاح');
    }

    public function show(int $id)
    {
        $tenantId = $this->tenantId();
        $customer = $this->findTenantCustomerOrFail($id);

        $account = null;
        $balances = collect();
        $lines = collect();

        // KPIs (اختياري للـ Blade)
        $kpis = [
            'total_debit' => 0.0,
            'total_credit' => 0.0,
            'net_move' => 0.0, // debit - credit
        ];

        // ✅ العميل مرتبط برقم حساب؟
        $accountNumber = (string) ($customer->account_number ?? '');
        $accountNumber = trim($accountNumber);

        if ($accountNumber !== '') {

            // ✅ الحساب المالي المرتبط
            $account = Account::with(['type', 'parent'])
                ->where('user_id', $tenantId)
                ->where('account_number', $accountNumber)
                ->first();

            if ($account) {

                // ✅ أرصدة الحساب (حسب العملة/الفرع)
                $balances = \App\Models\AccountBalance::query()
                    ->where('user_id', $tenantId)
                    ->where('account_id', $account->id)
                    ->orderBy('currency_code')
                    ->orderBy('branch_id')
                    ->get();

                /**
                 * ✅ الحركات (آخر 50 حركة)
                 * - orderByDesc لجلب آخر حاجة
                 * - reverse علشان العرض يبقى تصاعدي ويتحسب running balance صح
                 */
                $linesDesc = \App\Models\JournalEntryLine::with([
                    'journalEntry', // belongsTo JournalEntry
                    'account',      // belongsTo Account (اختياري للعرض)
                ])
                    ->where('user_id', $tenantId)
                    ->where('account_id', $account->id)
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get();

                $lines = $linesDesc->reverse()->values();

                $kpis['total_debit'] = round((float) $lines->sum('debit'), 2);
                $kpis['total_credit'] = round((float) $lines->sum('credit'), 2);
                $kpis['net_move'] = round($kpis['total_debit'] - $kpis['total_credit'], 2);
            }
        }

        return view('admin.customers.show', compact(
            'customer',
            'account',
            'balances',
            'lines',
            'kpis'
        ));
    }

    public function edit(int $id)
    {
        $customer = $this->findTenantCustomerOrFail($id);

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, int $id, UpdateCustomerWithAccount $action)
    {

        $tenantId = $this->tenantId();
        $customer = $this->findTenantCustomerOrFail($id);

        DB::transaction(function () use ($request, $action, $tenantId, $customer) {
            $action->handle(
                tenantId: $tenantId,
                customer: $customer,
                payload: $request->validated(),
                actorName: $this->tenantActorName(),
                imageFile: $request->file('image')
            );
        });

        return redirect()
            ->route('customers.index')
            ->with('success', 'تم تعديل العميل وتحديث حسابه المالي بنجاح');
    }

    public function destroy(int $id)
    {
        $customer = $this->findTenantCustomerOrFail($id);
        $tenantId = $this->tenantId();

        DB::transaction(function () use ($customer, $tenantId) {

            if (! empty($customer->account_number)) {
                Account::query()
                    ->where('user_id', $tenantId)
                    ->where('account_number', $customer->account_number)
                    ->delete();
            }

            if ($customer->image && Storage::disk('public')->exists($customer->image)) {
                Storage::disk('public')->delete($customer->image);
            }

            $customer->delete();
        });

        return redirect()
            ->route('customers.index')
            ->with('success', 'تم حذف العميل وحسابه المالي بنجاح');
    }

    public function toggleStatus(int $id)
    {
        $customer = $this->findTenantCustomerOrFail($id);

        $tenantId = $this->tenantId(); // owner/tenant id
        $actor = $this->tenantActorName();

        if (empty($customer->account_number)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير الحالة: العميل غير مرتبط برقم حساب.',
            ], 422);
        }

        $account = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', $customer->account_number)
            ->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير الحالة: لا يوجد حساب مرتبط بهذا العميل في شجرة الحسابات.',
            ], 422);
        }

        if ($customer->status === true) {
            $balance = (float) ($account->current_balance ?? 0);

            if (abs($balance) > 0.00001) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إيقاف العميل لأن رصيد حسابه الحالي ليس صفرًا.',
                ], 422);
            }

            if ($account->children()->where('status', true)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إيقاف العميل لأن لديه حسابات فرعية نشطة.',
                ], 422);
            }
        }

        $newStatus = ! $customer->status;

        DB::transaction(function () use ($customer, $account, $newStatus, $actor) {
            $customer->update([
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
            'message' => $newStatus ? 'تم تفعيل العميل' : 'تم إيقاف العميل',
        ]);
    }
}
