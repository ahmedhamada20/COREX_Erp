<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\TreasuriesDeliveriesDataTable;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Treasuries;
use App\Models\TreasuriesDelivery;
use App\Models\UserShift;
use App\Services\Accounting\TreasuryPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TreasuryDeliveryController extends AdminBaseController
{
    private function actorId(): int
    {
        return (int) auth()->id();
    }

    private function actorName(): string
    {
        return auth()->user()->name ?? (string) auth()->id();
    }

    private function authorizeOwner(Treasuries $treasury): void
    {
        abort_if((int) $treasury->user_id !== $this->ownerId(), 403);
    }

    private function ensureDeliveryBelongsToTreasury(Treasuries $treasury, TreasuriesDelivery $delivery): void
    {
        abort_unless((int) $delivery->user_id === $this->ownerId(), 404);

        $belongs = ((int) $delivery->from_treasury_id === (int) $treasury->id)
            || ((int) $delivery->to_treasury_id === (int) $treasury->id);

        abort_unless($belongs, 404);
    }

    private function currentOpenShiftOrFail(int $ownerId, int $actorId, int $treasuryId): UserShift
    {
        $shift = UserShift::query()
            ->where('user_id', $ownerId)
            ->where('actor_user_id', $actorId)
            ->where('treasury_id', $treasuryId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        abort_unless($shift, 403, 'لا يوجد شفت مفتوح على هذه الخزنة. افتح الشفت أولاً.');

        return $shift;
    }

    private function forbidIfShiftClosed(?int $shiftId): void
    {
        if (! $shiftId) {
            return;
        }

        $ownerId = $this->ownerId();

        $closed = UserShift::query()
            ->where('user_id', $ownerId)
            ->where('id', $shiftId)
            ->where('status', 'closed')
            ->exists();

        abort_if($closed, 403, 'لا يمكن تعديل/حذف حركة مرتبطة بشفت مغلق.');
    }

    private function nextReceiptNoLocked(int $ownerId, int $treasuryId, string $type): int
    {
        $treasury = Treasuries::query()
            ->where('user_id', $ownerId)
            ->where('id', $treasuryId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($type === 'payment') {
            $next = (int) $treasury->last_payment_receipt_no + 1;
            $treasury->update(['last_payment_receipt_no' => $next]);

            return $next;
        }

        $next = (int) $treasury->last_collection_receipt_no + 1;
        $treasury->update(['last_collection_receipt_no' => $next]);

        return $next;
    }

    /**
     * ✅ أهم نقطة: المستخدم لا يختار حساب محاسبي عشوائيًا
     * - customer => نجيب customer->account_id
     * - supplier => نجيب supplier->account_id
     * - general  => يسمح باختيار حساب "محدد/مسموح" فقط (بنفلتره)
     */
    private function resolveCounterpartyAccountId(int $ownerId, string $type, ?int $id, ?int $generalAccountId): int
    {
        if ($type === 'customer') {
            $c = Customer::query()
                ->where('user_id', $ownerId)
                ->where('id', (int) $id)
                ->firstOrFail();

            abort_unless(! empty($c->account_number), 422, 'العميل ليس له حساب محاسبي مرتبط.');
            $acc = Account::query()->where('user_id', $ownerId)->where('account_number', $c->account_number)->firstOrFail();

            return (int) $acc->id;
        }

        if ($type === 'supplier') {
            $s = Supplier::query()
                ->where('user_id', $ownerId)
                ->where('id', (int) $id)
                ->firstOrFail();

            abort_unless(! empty($s->account_number), 422, 'المورد ليس له حساب محاسبي مرتبط.');
            $acc = Account::query()->where('user_id', $ownerId)->where('account_number', $s->account_number)->firstOrFail();

            return (int) $acc->id;
        }

        // general: اختيار حساب عام لكن "محدود"
        // ✅ هنا بنسمح فقط بحسابات status=1 + (ولازم انت تحدد الفلتر حسب شجرة الحسابات عندك)
        // حاليًا: بنسمح بالحسابات اللي ليست خزائن (مش مرتبطة بخزنة) لتقليل الأخطاء
        if ($type === 'general') {
            abort_unless(! empty($generalAccountId), 422, 'اختر الحساب العام.');

            $acc = Account::query()
                ->where('user_id', $ownerId)
                ->where('status', 1)
                ->where('id', (int) $generalAccountId)
                ->firstOrFail();

            // منع اختيار حساب خزنة كطرف مقابل (غلط محاسبي في الغالب)
            $isTreasuryAccount = Treasuries::query()
                ->where('user_id', $ownerId)
                ->where('account_id', $acc->id)
                ->exists();

            abort_if($isTreasuryAccount, 422, 'لا يمكن اختيار حساب خزنة كطرف مقابل.');

            return (int) $acc->id;
        }

        abort(422, 'نوع الطرف المقابل غير صالح.');
    }

    public function index(Treasuries $treasury, TreasuriesDeliveriesDataTable $dataTable)
    {
        $this->authorizeOwner($treasury);

        // رصيد تشغيلي مؤقت (تقدر بعدين تستبدله برصيد الحساب)
        $ownerId = $this->ownerId();

        $in = TreasuriesDelivery::query()
            ->where('user_id', $ownerId)
            ->where('to_treasury_id', $treasury->id)
            ->sum('amount');

        $out = TreasuriesDelivery::query()
            ->where('user_id', $ownerId)
            ->where('from_treasury_id', $treasury->id)
            ->sum('amount');

        $balance = (float) $in - (float) $out;

        return $dataTable
            ->withTreasury($treasury)
            ->render('admin.treasuries.deliveries.index', compact('treasury', 'balance'));
    }

    public function create(Treasuries $treasury)
    {
        $this->authorizeOwner($treasury);

        if (! $treasury->account_id) {
            return redirect()->route('treasuries.edit', $treasury->id)
                ->with('error', 'الخزنة غير مربوطة بحساب محاسبي. افتح تعديل الخزنة واختر الحساب.');
        }

        $ownerId = $this->ownerId();

        $balance = $this->treasuryBalance($ownerId, (int) $treasury->id);

        $treasuries = Treasuries::query()
            ->where('user_id', $ownerId)
            ->where('status', 1)
            ->orderByDesc('is_master')
            ->orderBy('name')
            ->get();

        $customers = Customer::query()
            ->where('user_id', $ownerId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $suppliers = Supplier::query()
            ->where('user_id', $ownerId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $generalAccounts = Account::query()
            ->where('user_id', $ownerId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'account_number']);

        $in = TreasuriesDelivery::where('user_id', $ownerId)->where('to_treasury_id', $treasury->id)->sum('amount');
        $out = TreasuriesDelivery::where('user_id', $ownerId)->where('from_treasury_id', $treasury->id)->sum('amount');
        $balance = (float) $in - (float) $out;

        return view('admin.treasuries.deliveries.create', compact(
            'treasury',
            'balance',
            'treasuries',
            'customers',
            'suppliers',
            'generalAccounts',
            'balance'
        ));
    }

    public function store(Request $request, Treasuries $treasury, TreasuryPostingService $posting)
    {
        $this->authorizeOwner($treasury);

        if (! $treasury->account_id) {
            return back()->withInput()->with('error', 'الخزنة غير مربوطة بحساب محاسبي (account_id). اربطها أولاً.');
        }

        $ownerId = $this->ownerId();
        $actorId = $this->actorId();
        $actor = $this->actorName();

        $shift = $this->currentOpenShiftOrFail($ownerId, $actorId, (int) $treasury->id);

        $data = $request->validate([
            'type' => ['required', Rule::in(['collection', 'payment', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'doc_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'other_treasury_id' => [
                'nullable',
                'integer',
                Rule::exists('treasuries', 'id')->where(fn ($q) => $q->where('user_id', $ownerId)->where('status', 1)),
            ],

            // ✅ طرف مقابل (business)
            'counterparty_type' => ['nullable', Rule::in(['customer', 'supplier', 'general'])],
            'customer_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'general_account_id' => ['nullable', 'integer'],
        ]);

        $docDate = $data['doc_date'] ?? now()->toDateString();

        $fromId = null;
        $toId = null;

        // ✅ خزنة الاستلام/الإرسال
        if ($data['type'] === 'collection') {
            $toId = $treasury->id;
        } elseif ($data['type'] === 'payment') {
            $fromId = $treasury->id;
        } else {
            $fromId = $treasury->id;
            $toId = (int) ($data['other_treasury_id'] ?? 0);

            if (! $toId) {
                return back()->withErrors(['other_treasury_id' => 'اختر خزنة الاستلام.'])->withInput();
            }
            if ($toId === (int) $treasury->id) {
                return back()->withErrors(['other_treasury_id' => 'لا يمكن التحويل لنفس الخزنة.'])->withInput();
            }

            // ✅ خزنة الاستلام لازم لها حساب
            $toTreasury = Treasuries::query()->where('user_id', $ownerId)->findOrFail($toId);
            if (! $toTreasury->account_id) {
                return back()->withErrors(['other_treasury_id' => 'خزنة الاستلام غير مربوطة بحساب محاسبي.'])->withInput();
            }
        }

        // ✅ الطرف المقابل مطلوب في القبض/الصرف فقط
        $counterpartyAccountId = null;
        $counterpartyType = null;
        $counterpartyId = null;

        if (in_array($data['type'], ['collection', 'payment'], true)) {
            $counterpartyType = (string) ($data['counterparty_type'] ?? '');
            if ($counterpartyType === '') {
                return back()->withErrors(['counterparty_type' => 'اختر نوع الطرف المقابل.'])->withInput();
            }

            if ($counterpartyType === 'customer') {
                $counterpartyId = (int) ($data['customer_id'] ?? 0);
                if (! $counterpartyId) {
                    return back()->withErrors(['customer_id' => 'اختر العميل.'])->withInput();
                }
            } elseif ($counterpartyType === 'supplier') {
                $counterpartyId = (int) ($data['supplier_id'] ?? 0);
                if (! $counterpartyId) {
                    return back()->withErrors(['supplier_id' => 'اختر المورد.'])->withInput();
                }
            } else { // general
                $counterpartyId = null;
            }

            $counterpartyAccountId = $this->resolveCounterpartyAccountId(
                $ownerId,
                $counterpartyType,
                $counterpartyId,
                $data['general_account_id'] ?? null
            );
        }

        $delivery = DB::transaction(function () use (
            $ownerId, $actorId, $actor, $treasury, $shift, $data, $fromId, $toId, $docDate, $counterpartyAccountId, $posting
        ) {

            // ✅ Lock خزنة المصدر (لمنع سباق صرف)
            $lockedTreasury = Treasuries::query()
                ->where('user_id', $ownerId)
                ->where('id', (int) $treasury->id)
                ->lockForUpdate()
                ->firstOrFail();

            // ✅ لو صرف أو تحويل => لازم الرصيد يكفي
            if (in_array($data['type'], ['payment', 'transfer'], true)) {

                $in = TreasuriesDelivery::query()
                    ->where('user_id', $ownerId)
                    ->where('to_treasury_id', (int) $lockedTreasury->id)
                    ->sum('amount');

                $out = TreasuriesDelivery::query()
                    ->where('user_id', $ownerId)
                    ->where('from_treasury_id', (int) $lockedTreasury->id)
                    ->sum('amount');

                $balance = (float) $in - (float) $out;
                $amount = (float) $data['amount'];

                if ($balance < $amount) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => 'الرصيد غير كافي. الرصيد الحالي: '.number_format($balance, 2),
                    ]);
                }
            }

            // ✅ لو تحويل: Lock خزنة الاستلام كمان (اختياري لكنه أفضل)
            if ($data['type'] === 'transfer') {
                Treasuries::query()
                    ->where('user_id', $ownerId)
                    ->where('id', (int) $toId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $receiptNo = $this->nextReceiptNoLocked($ownerId, (int) $treasury->id, $data['type']);

            $delivery = TreasuriesDelivery::create([
                'user_id' => $ownerId,
                'actor_user_id' => $actorId,
                'shift_id' => $shift->id,

                'type' => $data['type'],
                'from_treasury_id' => $fromId,
                'to_treasury_id' => $toId,

                'amount' => $data['amount'],
                'receipt_no' => $receiptNo,
                'doc_date' => $docDate,

                'counterparty_account_id' => $counterpartyAccountId,

                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'updated_by' => $actor,
            ]);

            $posting->postDelivery($delivery);

            return $delivery;
        });

        return redirect()
            ->route('treasuries.deliveries.index', $treasury->id)
            ->with('success', 'تم تسجيل الحركة بنجاح');
    }

    public function show(Treasuries $treasury, TreasuriesDelivery $delivery)
    {
        $this->authorizeOwner($treasury);
        $this->ensureDeliveryBelongsToTreasury($treasury, $delivery);

        $delivery->load(['actor', 'fromTreasury', 'toTreasury', 'shift']);

        // ✅ اسم الطرف المقابل (عرض فقط)
        $ownerId = $this->ownerId();
        $counterpartyName = null;

        if ($delivery->counterparty_type === 'customer' && $delivery->counterparty_id) {
            $counterpartyName = Customer::where('user_id', $ownerId)->where('id', $delivery->counterparty_id)->value('name');
        } elseif ($delivery->counterparty_type === 'supplier' && $delivery->counterparty_id) {
            $counterpartyName = Supplier::where('user_id', $ownerId)->where('id', $delivery->counterparty_id)->value('name');
        } elseif ($delivery->counterparty_type === 'general' && $delivery->counterparty_account_id) {
            $counterpartyName = Account::where('user_id', $ownerId)->where('id', $delivery->counterparty_account_id)->value('name');
        }

        return view('admin.treasuries.deliveries.show', compact('treasury', 'delivery', 'counterpartyName'));
    }

    public function edit(Treasuries $treasury, TreasuriesDelivery $delivery)
    {
        $this->authorizeOwner($treasury);
        $this->ensureDeliveryBelongsToTreasury($treasury, $delivery);
        $this->forbidIfShiftClosed($delivery->shift_id);

        $ownerId = $this->ownerId();

        $treasuries = Treasuries::query()
            ->where('user_id', $ownerId)
            ->where('status', 1)
            ->orderByDesc('is_master')
            ->orderBy('name')
            ->get();

        $customers = Customer::query()->where('user_id', $ownerId)->orderBy('name')->get(['id', 'name']);
        $suppliers = Supplier::query()->where('user_id', $ownerId)->orderBy('name')->get(['id', 'name']);

        $generalAccounts = Account::query()
            ->where('user_id', $ownerId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'account_number']);

        return view('admin.treasuries.deliveries.edit', compact('treasury', 'delivery', 'treasuries', 'customers', 'suppliers', 'generalAccounts'));
    }

    public function update(Request $request, Treasuries $treasury, TreasuriesDelivery $delivery, TreasuryPostingService $posting)
    {
        $this->authorizeOwner($treasury);
        $this->ensureDeliveryBelongsToTreasury($treasury, $delivery);
        $this->forbidIfShiftClosed($delivery->shift_id);

        if (! $treasury->account_id) {
            return back()->withInput()->with('error', 'الخزنة غير مربوطة بحساب محاسبي (account_id). اربطها أولاً.');
        }

        $ownerId = $this->ownerId();
        $actorId = $this->actorId();
        $actor = $this->actorName();

        $shift = $this->currentOpenShiftOrFail($ownerId, $actorId, (int) $treasury->id);

        $data = $request->validate([
            'type' => ['required', Rule::in(['collection', 'payment', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'doc_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'other_treasury_id' => [
                'nullable',
                'integer',
                Rule::exists('treasuries', 'id')->where(fn ($q) => $q->where('user_id', $ownerId)->where('status', 1)),
            ],

            'counterparty_type' => ['nullable', Rule::in(['customer', 'supplier', 'general'])],
            'customer_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'general_account_id' => ['nullable', 'integer'],
        ]);

        $docDate = $data['doc_date'] ?? $delivery->doc_date ?? now()->toDateString();

        $fromId = null;
        $toId = null;

        if ($data['type'] === 'collection') {
            $toId = $treasury->id;
        } elseif ($data['type'] === 'payment') {
            $fromId = $treasury->id;
        } else {
            $fromId = $treasury->id;
            $toId = (int) ($data['other_treasury_id'] ?? 0);

            if (! $toId) {
                return back()->withErrors(['other_treasury_id' => 'اختر خزنة الاستلام.'])->withInput();
            }
            if ($toId === (int) $treasury->id) {
                return back()->withErrors(['other_treasury_id' => 'لا يمكن التحويل لنفس الخزنة.'])->withInput();
            }

            $toTreasury = Treasuries::query()->where('user_id', $ownerId)->findOrFail($toId);
            if (! $toTreasury->account_id) {
                return back()->withErrors(['other_treasury_id' => 'خزنة الاستلام غير مربوطة بحساب محاسبي.'])->withInput();
            }
        }

        $counterpartyAccountId = null;
        $counterpartyType = null;
        $counterpartyId = null;

        if (in_array($data['type'], ['collection', 'payment'], true)) {
            $counterpartyType = (string) ($data['counterparty_type'] ?? '');
            if ($counterpartyType === '') {
                return back()->withErrors(['counterparty_type' => 'اختر نوع الطرف المقابل.'])->withInput();
            }

            if ($counterpartyType === 'customer') {
                $counterpartyId = (int) ($data['customer_id'] ?? 0);
                if (! $counterpartyId) {
                    return back()->withErrors(['customer_id' => 'اختر العميل.'])->withInput();
                }
            } elseif ($counterpartyType === 'supplier') {
                $counterpartyId = (int) ($data['supplier_id'] ?? 0);
                if (! $counterpartyId) {
                    return back()->withErrors(['supplier_id' => 'اختر المورد.'])->withInput();
                }
            }

            $counterpartyAccountId = $this->resolveCounterpartyAccountId(
                $ownerId,
                $counterpartyType,
                $counterpartyId,
                $data['general_account_id'] ?? null
            );
        }

        DB::transaction(function () use ($delivery, $shift, $actorId, $actor, $data, $fromId, $toId, $docDate, $counterpartyType, $counterpartyId, $counterpartyAccountId, $posting) {

            $delivery->update([
                'actor_user_id' => $actorId,
                'shift_id' => $shift->id,

                'type' => $data['type'],
                'from_treasury_id' => $fromId,
                'to_treasury_id' => $toId,

                'amount' => $data['amount'],
                'doc_date' => $docDate,

                'counterparty_type' => $counterpartyType,
                'counterparty_id' => $counterpartyId,
                'counterparty_account_id' => $counterpartyAccountId,

                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'updated_by' => $actor,
            ]);

            // ✅ عكس القديم + إنشاء قيد جديد
            $posting->repostOnUpdate($delivery);
        });

        return redirect()
            ->route('treasuries.deliveries.index', $treasury->id)
            ->with('success', 'تم تحديث الحركة بنجاح');
    }

    public function destroy(Treasuries $treasury, TreasuriesDelivery $delivery, TreasuryPostingService $posting)
    {
        $this->authorizeOwner($treasury);
        $this->ensureDeliveryBelongsToTreasury($treasury, $delivery);
        $this->forbidIfShiftClosed($delivery->shift_id);

        $ownerId = $this->ownerId();
        $actorId = $this->actorId();
        $this->currentOpenShiftOrFail($ownerId, $actorId, (int) $treasury->id);

        DB::transaction(function () use ($delivery, $posting) {
            $posting->reverseOnDelete($delivery, 'تم حذف حركة خزنة');
            $delivery->delete();
        });

        return redirect()
            ->route('treasuries.deliveries.index', $treasury->id)
            ->with('success', 'تم حذف الحركة بنجاح');
    }

    private function treasuryBalance(int $ownerId, int $treasuryId): float
    {
        $in = TreasuriesDelivery::query()
            ->where('user_id', $ownerId)
            ->where('to_treasury_id', $treasuryId)
            ->sum('amount');

        $out = TreasuriesDelivery::query()
            ->where('user_id', $ownerId)
            ->where('from_treasury_id', $treasuryId)
            ->sum('amount');

        return (float) $in - (float) $out;
    }
}
