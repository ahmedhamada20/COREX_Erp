<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\TreasuriesDataTable;
use App\Http\Requests\StoreTreasuryRequest;
use App\Http\Requests\UpdateTreasuryRequest;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Treasuries;
use App\Models\TreasuriesDelivery;
use App\Models\UserShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TreasuriesController extends AdminBaseController
{
    private function authorizeOwner(Treasuries $treasury): void
    {
        abort_if((int) $treasury->user_id !== $this->ownerId(), 403);
    }

    private function actorName(): string
    {
        return auth()->user()->name ?? (string) auth()->id();
    }

    private function makeCode(int $ownerId): string
    {
        return 'TR-'.$ownerId.'-'.Str::upper(Str::random(6));
    }

    /**
     * ✅ Assets type id per owner (multi-tenant safe)
     */
    private function assetTypeId(int $ownerId): int
    {
        $type = AccountType::query()
            ->where('user_id', $ownerId)
            ->where('name', 'الأصول')
            ->first();

        if (! $type) {
            $type = AccountType::create([
                'user_id' => $ownerId,
                'name' => 'الأصول',
                'status' => true,
            ]);
        }

        return (int) $type->id;
    }

    /**
     * ✅ Upsert account by account_number (like your seeder)
     * - keep current_balance if exists (audit safe)
     */
    private function upsertAccount(int $ownerId, string $actor, array $data): Account
    {
        $start = (float) ($data['start_balance'] ?? 0);

        $existing = Account::query()
            ->where('user_id', $ownerId)
            ->where('account_number', (string) $data['account_number'])
            ->first();

        return Account::updateOrCreate(
            [
                'user_id' => $ownerId,
                'account_number' => (string) $data['account_number'],
            ],
            [
                'account_type_id' => $data['account_type_id'],
                'parent_account_id' => $data['parent_account_id'] ?? null,
                'name' => $data['name'],
                'start_balance' => $start,
                'current_balance' => $existing?->current_balance ?? ($data['current_balance'] ?? $start),
                'status' => $data['status'] ?? true,
                'notes' => $data['notes'] ?? null,
                'updated_by' => $actor,

                // لو عندك عمود date فعلاً في جدول accounts سيبه، لو مش موجود احذفه:
                // 'date'           => $data['date'] ?? now()->toDateString(),
            ]
        );
    }

    /**
     * ✅ Ensure chart path exists and return 1100 "الصندوق" account:
     * 1000 الأصول -> 1010 الأصول المتداولة -> 1100 الصندوق
     */
    private function treasuryParentAccount(int $ownerId, string $actor): Account
    {
        return DB::transaction(function () use ($ownerId, $actor) {

            // لو 1100 موجود خلاص
            $cash = Account::query()
                ->where('user_id', $ownerId)
                ->where('account_number', '1100')
                ->first();

            if ($cash) {
                return $cash;
            }

            $assetTypeId = $this->assetTypeId($ownerId);

            // 1000
            $assets = $this->upsertAccount($ownerId, $actor, [
                'account_type_id' => $assetTypeId,
                'parent_account_id' => null,
                'name' => 'الأصول',
                'account_number' => '1000',
            ]);

            // 1010
            $currentAssets = $this->upsertAccount($ownerId, $actor, [
                'account_type_id' => $assetTypeId,
                'parent_account_id' => $assets->id,
                'name' => 'الأصول المتداولة',
                'account_number' => '1010',
            ]);

            // 1100
            $cash = $this->upsertAccount($ownerId, $actor, [
                'account_type_id' => $assetTypeId,
                'parent_account_id' => $currentAssets->id,
                'name' => 'الصندوق',
                'account_number' => '1100',
            ]);

            return $cash;
        });
    }

    /**
     * ✅ Create treasury account under 1100 with sequential numbers: 1101, 1102, ...
     * - lockForUpdate to avoid duplicates
     * - if same name exists, return it
     */
    private function createTreasuryAccount(int $ownerId, string $actor, string $treasuryName): Account
    {
        return DB::transaction(function () use ($ownerId, $actor, $treasuryName) {

            $parent = $this->treasuryParentAccount($ownerId, $actor);

            $existingByName = Account::query()
                ->where('user_id', $ownerId)
                ->where('parent_account_id', $parent->id)
                ->where('name', 'الصندوق - '.$treasuryName)

                ->first();

            if ($existingByName) {
                return $existingByName;
            }

            // قفل آخر رقم تحت 1100
            $last = Account::query()
                ->where('user_id', $ownerId)
                ->where('parent_account_id', $parent->id)
                ->where('account_number', 'regexp', '^[0-9]+$') // لو MySQL
                ->lockForUpdate()
                ->orderByRaw('CAST(account_number AS UNSIGNED) DESC')
                ->first();

            $nextNo = 1101;
            if ($last && is_numeric($last->account_number)) {
                $nextNo = ((int) $last->account_number) + 1;
            }

            return Account::create([
                'user_id' => $ownerId,
                'account_type_id' => $parent->account_type_id,
                'parent_account_id' => $parent->id,
                'name' => $treasuryName,
                'account_number' => (string) $nextNo,
                'start_balance' => 0,
                'current_balance' => 0,
                'status' => true,
                'updated_by' => $actor,

                // لو عندك عمود date فعلاً سيبه، لو مش موجود امسحه:
                // 'date'          => now()->toDateString(),
            ]);
        });
    }

    public function index(TreasuriesDataTable $dataTable)
    {
        return $dataTable->render('admin.treasuries.index');
    }

    public function create()
    {
        return view('admin.treasuries.create');
    }

    public function store(StoreTreasuryRequest $request)
    {
        $ownerId = $this->ownerId();
        $actor = $this->actorName();
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $isMaster = $request->boolean('is_master');
            $status = $request->boolean('status');

            $code = $validated['code'] ?? null;
            if (empty($code)) {
                do {
                    $code = $this->makeCode($ownerId);
                } while (Treasuries::where('user_id', $ownerId)->where('code', $code)->exists());
            }

            if ($isMaster) {
                Treasuries::where('user_id', $ownerId)
                    ->where('is_master', true)
                    ->update([
                        'is_master' => false,
                        'updated_by' => $actor,
                    ]);
            }

            // ✅ AUTO: create/attach accounting account under 1100
            $acc = $this->createTreasuryAccount($ownerId, $actor, $validated['name']);

            Treasuries::create([
                'user_id' => $ownerId,
                'account_id' => $acc->id,
                'name' => $validated['name'],
                'code' => $code,
                'is_master' => $isMaster,
                'status' => $status,
                'last_reconciled_at' => $validated['last_reconciled_at'] ?? now()->toDateString(),
                'last_payment_receipt_no' => 0,
                'last_collection_receipt_no' => 0,
                'updated_by' => $actor,
            ]);

            DB::commit();

            return redirect()->route('treasuries.index')
                ->with('success', 'تم إضافة الخزنة بنجاح (وتم إنشاء حسابها المحاسبي تلقائيًا).');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Treasury store failed', [
                'tenant_user_id' => $ownerId,
                'actor_user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'حدث خطأ أثناء حفظ الخزنة');
        }
    }

    public function show(Treasuries $treasury)
    {
        $this->authorizeOwner($treasury);

        $treasury->load('account');

        return view('admin.treasuries.show', compact('treasury'));
    }

    public function edit(Treasuries $treasury)
    {
        $this->authorizeOwner($treasury);

        $treasury->load('account');

        return view('admin.treasuries.edit', compact('treasury'));
    }

    public function update(UpdateTreasuryRequest $request, Treasuries $treasury)
    {
        $this->authorizeOwner($treasury);

        $ownerId = $this->ownerId();
        $actor = $this->actorName();
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $isMaster = $request->boolean('is_master');
            $status = $request->boolean('status');

            $code = $validated['code'] ?? $treasury->code;
            if (empty($code)) {
                do {
                    $code = $this->makeCode($ownerId);
                } while (
                    Treasuries::where('user_id', $ownerId)
                        ->where('code', $code)
                        ->where('id', '!=', $treasury->id)
                        ->exists()
                );
            }

            if ($isMaster) {
                Treasuries::where('user_id', $ownerId)
                    ->where('id', '!=', $treasury->id)
                    ->where('is_master', true)
                    ->update([
                        'is_master' => false,
                        'updated_by' => $actor,
                    ]);
            }

            // ✅ legacy treasuries without account_id
            if (! $treasury->account_id) {
                $acc = $this->createTreasuryAccount($ownerId, $actor, $validated['name']);
                $treasury->account_id = $acc->id;
            } else {
                // ✅ update linked account name
                Account::query()
                    ->where('user_id', $ownerId)
                    ->where('id', $treasury->account_id)
                    ->update([
                        'name' => 'الصندوق - '.$validated['name'],
                        'updated_by' => $actor,
                    ]);
            }

            $treasury->update([
                'account_id' => $treasury->account_id,
                'name' => $validated['name'],
                'code' => $code,
                'is_master' => $isMaster,
                'status' => $status,
                'last_reconciled_at' => $validated['last_reconciled_at'] ?? $treasury->last_reconciled_at,
                'updated_by' => $actor,
            ]);

            DB::commit();

            return redirect()->route('treasuries.index')->with('success', 'تم تحديث الخزنة بنجاح');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Treasury update failed', [
                'tenant_user_id' => $ownerId,
                'actor_user_id' => auth()->id(),
                'treasury_id' => $treasury->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الخزنة');
        }
    }

    public function destroy(Treasuries $treasury)
    {
        $this->authorizeOwner($treasury);

        $ownerId = $this->ownerId();

        try {
            if ((bool) $treasury->is_master) {
                return back()->with('error', 'لا يمكن حذف الخزنة الرئيسية. قم بتعيين خزنة أخرى كرئيسية أولاً.');
            }

            $hasOpenShift = UserShift::query()
                ->where('user_id', $ownerId)
                ->where('treasury_id', $treasury->id)
                ->where('status', 'open')
                ->exists();

            if ($hasOpenShift) {
                return back()->with('error', 'لا يمكن حذف خزنة عليها شفت مفتوح. اقفل الشفت أولاً.');
            }

            $hasMoves = TreasuriesDelivery::query()
                ->where('user_id', $ownerId)
                ->where(function ($q) use ($treasury) {
                    $q->where('from_treasury_id', $treasury->id)
                        ->orWhere('to_treasury_id', $treasury->id);
                })
                ->exists();

            if ($hasMoves) {
                return back()->with('error', 'لا يمكن حذف خزنة عليها حركات. (الأفضل تعطيلها بدل الحذف).');
            }

            // الأفضل Audit: ما تمسحش الحساب المحاسبي
            // Account::where('user_id', $ownerId)->where('id', $treasury->account_id)->delete();

            $treasury->delete();

            return redirect()->route('treasuries.index')->with('success', 'تم حذف الخزنة بنجاح');
        } catch (\Throwable $e) {
            Log::error('Treasury delete failed', [
                'tenant_user_id' => $ownerId,
                'actor_user_id' => auth()->id(),
                'treasury_id' => $treasury->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'تعذر حذف الخزنة');
        }
    }

    public function setMaster(Treasuries $treasury)
    {
        $this->authorizeOwner($treasury);

        $ownerId = $this->ownerId();
        $actor = $this->actorName();

        try {
            DB::beginTransaction();

            if (! (bool) $treasury->status) {
                DB::rollBack();

                return back()->with('error', 'لا يمكن تعيين خزنة غير مفعلة كرئيسية.');
            }

            Treasuries::where('user_id', $ownerId)->lockForUpdate()->get();

            Treasuries::where('user_id', $ownerId)
                ->where('is_master', true)
                ->update([
                    'is_master' => false,
                    'updated_by' => $actor,
                ]);

            $treasury->update([
                'is_master' => true,
                'updated_by' => $actor,
            ]);

            DB::commit();

            return back()->with('success', 'تم تعيين الخزنة كرئيسية بنجاح');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Treasury setMaster failed', [
                'tenant_user_id' => $ownerId,
                'actor_user_id' => auth()->id(),
                'treasury_id' => $treasury->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'تعذر تعيين الخزنة كرئيسية');
        }
    }
}
