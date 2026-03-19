<?php

namespace App\Http\Controllers\Admin;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends AdminBaseController
{
    private function authorizeOwner(Account $account): void
    {
        abort_if((int) $account->user_id !== $this->ownerId(), 403);
    }

    private function baseQuery(Request $request, int $ownerId)
    {
        $q = Account::query()
            ->where('user_id', $ownerId)
            ->mine()
            ->with(['type', 'parent'])
            ->latest('id'); // keyset pagination based on id

        if ($request->filled('search')) {
            $search = trim($request->search);
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('account_type_id')) {
            $q->where('account_type_id', (int) $request->account_type_id);
        }

        if ($request->filled('status')) {
            $q->where('status', (int) $request->status);
        }

        if ($request->filled('is_root')) {
            ((int) $request->is_root === 1)
                ? $q->whereNull('parent_account_id')
                : $q->whereNotNull('parent_account_id');
        }

        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->date_to);
        }

        return $q;
    }

    public function index(Request $request)
    {
        $ownerId = $this->ownerId();
        $q = $this->baseQuery($request, $ownerId);

        // ✅ أول دفعة للجدول (بدون paginate)
        $items = (clone $q)->limit(5)->get();

        // KPIs
        $totalAccounts = (clone $q)->count();
        $activeAccounts = (clone $q)->where('status', true)->count();

        // Tree
        $roots = Account::query()
            ->where('user_id', $ownerId)
            ->mine()
            ->roots()
            ->with(['type', 'descendants.type', 'descendants.parent'])
            ->orderBy('id')
            ->get();

        // Summary
        $summary = Account::query()
            ->where('user_id', $ownerId)
            ->mine()
            ->selectRaw('COALESCE(SUM(start_balance),0) as total_start')
            ->selectRaw('COALESCE(SUM(current_balance),0) as total_current')
            ->first();

        $types = AccountType::query()
            ->where('user_id', $ownerId)
            ->orderBy('name')
            ->get();

        return view('admin.accounts.index', compact(
            'items', 'roots', 'summary', 'types', 'totalAccounts', 'activeAccounts'
        ));
    }

    // ✅ Infinite Scroll endpoint (JSON)
    public function ajax(Request $request)
    {
        $ownerId = $this->ownerId();
        $q = $this->baseQuery($request, $ownerId);

        $limit = max(10, min((int) $request->get('limit', 50), 200));
        $cursor = $request->get('cursor'); // آخر ID تم تحميله

        if ($cursor) {
            // keyset pagination: next rows older than cursor
            $q->where('id', '<', (int) $cursor);
        }

        // نجيب limit+1 عشان نعرف هل فيه المزيد
        $items = $q->limit($limit + 1)->get();

        $hasMore = $items->count() > $limit;
        if ($hasMore) {
            $items = $items->take($limit);
        }

        $nextCursor = $items->last()?->id;

        $html = view('admin.accounts.partials.table-rows', compact('items'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
        ]);
    }

    public function create(Request $request)
    {
        $ownerId = $this->ownerId();

        $types = AccountType::query()
            ->where('user_id', $ownerId)
            ->orderBy('name')
            ->get();

        $parents = Account::query()
            ->where('user_id', $ownerId)
            ->mine()
            ->orderBy('name')
            ->get();

        $selectedParentId = null;

        if ($request->filled('parent_account_id')) {
            $selectedParentId = Account::query()
                ->where('user_id', $ownerId)
                ->mine()
                ->where('id', (int) $request->parent_account_id)
                ->value('id');
        }

        return view('admin.accounts.create', compact('types', 'parents', 'selectedParentId'));
    }

    public function store(Request $request)
    {
        $ownerId = $this->ownerId();

        $validated = $request->validate([
            'account_type_id' => [
                'required',
                'integer',
                Rule::exists('account_types', 'id')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
            'parent_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'start_balance' => ['nullable', 'numeric'],
            'current_balance' => ['nullable', 'numeric'],
            'other_table_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'date' => ['nullable', 'date'],
        ]);

        $validated['user_id'] = $ownerId;
        $validated['status'] = (bool) ($validated['status'] ?? false);
        $validated['updated_by'] = auth()->user()->name ?? (string) auth()->id();

        if (empty($validated['account_number'])) {
            $validated['account_number'] = $this->nextAccountNumber();
        }

        Account::create($validated);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'تم إضافة الحساب بنجاح');
    }

    public function show(Account $account)
    {
        $ownerId = auth()->user()->owner_user_id ?? auth()->id();
        abort_if((int) $account->user_id !== (int) $ownerId, 403);

        // ✅ أرصدة الحساب (account_balances)
        $balances = \App\Models\AccountBalance::query()
            ->where('user_id', $ownerId)
            ->where('account_id', $account->id)
            ->orderBy('currency_code')
            ->orderByRaw('CASE WHEN branch_id IS NULL THEN 1 ELSE 0 END, branch_id')
            ->get();

        // ✅ Ledger lines
        $lines = \App\Models\JournalEntryLine::query()
            ->where('user_id', $ownerId)
            ->where('account_id', $account->id)
            ->with([
                'journalEntry:id,user_id,entry_number,entry_date,source,reference_type,reference_id,description,status',
            ])
            ->orderByDesc('id')
            ->paginate(25);

        // ✅ إجماليات كل الحركات على الحساب (مش بس الصفحة)
        $totals = \App\Models\JournalEntryLine::query()
            ->where('user_id', $ownerId)
            ->where('account_id', $account->id)
            ->selectRaw('COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(credit),0) as total_credit')
            ->first();

        $totalDebit = (float) ($totals->total_debit ?? 0);
        $totalCredit = (float) ($totals->total_credit ?? 0);

        $net = $totalDebit - $totalCredit; // + = مدين , - = دائن
        $netSide = $net >= 0 ? 'مدين' : 'دائن';
        $netAbs = abs($net);

        $account->load(['type', 'parent', 'children.type']);

        return view('admin.accounts.show', compact(
            'account',
            'balances',
            'lines',
            'totalDebit',
            'totalCredit',
            'net',
            'netSide',
            'netAbs'
        ));
    }

    public function edit(Account $account)
    {
        $this->authorizeOwner($account);
        $ownerId = $this->ownerId();

        $types = AccountType::query()
            ->where('user_id', $ownerId)
            ->orderBy('name')
            ->get();

        $parents = Account::query()
            ->where('user_id', $ownerId)
            ->mine()
            ->where('id', '!=', $account->id)
            ->orderBy('name')
            ->get();

        return view('admin.accounts.edit', compact('account', 'types', 'parents'));
    }

    public function update(Request $request, Account $account)
    {
        $this->authorizeOwner($account);

        $ownerId = $this->ownerId();
        $hasMovements = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->exists();

        $validated = $request->validate([
            'account_type_id' => [
                'required',
                'integer',
                Rule::exists('account_types', 'id')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],

            'parent_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('user_id', $ownerId)),
                Rule::notIn([$account->id]),
            ],

            'name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],

            'start_balance' => ['nullable', 'numeric'],
            'current_balance' => ['nullable', 'numeric'],

            'other_table_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'boolean'],
            'date' => ['nullable', 'date'],
        ]);

        // ✅ status الجديدة (لو مش مبعوتة خليها زي ما هي)
        $newStatus = array_key_exists('status', $validated)
            ? (bool) $validated['status']
            : (bool) $account->status;

        // ✅ هل المستخدم بيحاول يوقف الحساب الآن؟
        $isDisabling = ((bool) $account->status === true) && ($newStatus === false);

        // ✅ منع عمل Loop (على الأقل: منع النقل تحت ابن مباشر)
        if (! empty($validated['parent_account_id'])) {
            $newParentId = (int) $validated['parent_account_id'];

            if ($account->children()->whereKey($newParentId)->exists()) {
                return $this->failResponse($request, 'لا يمكن نقل الحساب تحت أحد فروعه (سيؤدي إلى حلقة).');
            }
        }

        if ($isDisabling) {

            if ($account->children()->where('status', true)->exists()) {
                return $this->failResponse($request, 'لا يمكن إيقاف الحساب لأن لديه حسابات فرعية نشطة.');
            }

            $balance = (float) ($account->current_balance ?? 0);
            if (abs($balance) > 0.00001) {
                return $this->failResponse($request, 'لا يمكن إيقاف الحساب لأن الرصيد الحالي ليس صفرًا.');
            }
        }

        // ✅ منع تعديل رصيد أول المدة بعد وجود حركات على الحساب
        if ($hasMovements && array_key_exists('start_balance', $validated)) {
            $newStart = is_null($validated['start_balance'])
                ? null
                : round((float) $validated['start_balance'], 2);

            $oldStart = is_null($account->start_balance)
                ? null
                : round((float) $account->start_balance, 2);

            if ($newStart !== $oldStart) {
                // لا تسمح بالتعديل اليدوي بعد وجود حركات
                unset($validated['start_balance']);

                return $this->failResponse(
                    $request,
                    'لا يمكن تعديل رصيد أول المدة بعد وجود حركات على الحساب. يجب إنشاء قيد تسوية محاسبي بدلاً من ذلك.'
                );
            }
        }

        $validated['status'] = $newStatus;
        $validated['updated_by'] = auth()->user()->name ?? (string) auth()->id();

        if (array_key_exists('start_balance', $validated)) {
            $validated['start_balance'] = is_null($validated['start_balance'])
                ? null
                : round((float) $validated['start_balance'], 2);
        }

        if (array_key_exists('current_balance', $validated)) {
            $validated['current_balance'] = is_null($validated['current_balance'])
                ? null
                : round((float) $validated['current_balance'], 2);
        }

        $account->update($validated);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'تم تحديث الحساب بنجاح');
    }

    private function failResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return back()
            ->withInput()
            ->withErrors(['status' => $message]);
    }

    public function destroy(Account $account)
    {
        $this->authorizeOwner($account);

        //        if (abs((float)$account->current_balance) > 0) {
        //            return back()->with('error', 'لا يمكن حذف الحساب لأن له رصيد حالي.');
        //        }

        if (! empty($account->current_balance)) {
            return back()->with('error', 'لا يمكن حذف الحساب لأن له رصيد حالي.');
        }
        if ($account->children()->exists()) {
            return back()->with('error', 'لا يمكن حذف الحساب لأنه يحتوي على حسابات فرعية.');
        }

        $account->delete();

        return redirect()
            ->route('accounts.index')
            ->with('success', 'تم حذف الحساب بنجاح');
    }

    public function toggleStatus(Account $account)
    {
        $this->authorizeOwner($account);

        if ($account->status === true) {
            $balance = (float) ($account->current_balance ?? 0);
            if (abs($balance) > 0.00001) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إيقاف الحساب لأن الرصيد الحالي ليس صفرًا.',
                ], 422);
            }

            if ($account->children()->where('status', true)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إيقاف الحساب لأن لديه حسابات فرعية نشطة.',
                ], 422);
            }
        }

        $account->status = ! $account->status;
        $account->updated_by = auth()->user()->name ?? (string) auth()->id();
        $account->save();

        return response()->json([
            'success' => true,
            'status' => $account->status,
            'message' => $account->status ? 'تم تفعيل الحساب' : 'تم إيقاف الحساب',
        ]);
    }

    private function nextAccountNumber(): string
    {
        $ownerId = $this->ownerId();

        $last = Account::query()
            ->where('user_id', $ownerId)
            ->whereNotNull('account_number')
            ->where('account_number', 'like', 'ACC-%')
            ->orderByDesc('id')
            ->value('account_number');

        $nextNumber = 1;

        if ($last && preg_match('/ACC-(\d+)/', $last, $m)) {
            $nextNumber = ((int) $m[1]) + 1;
        }

        return 'ACC-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
