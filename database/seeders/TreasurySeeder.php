<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Treasuries;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TreasurySeeder extends Seeder
{
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
            ]
        );
    }

    private function treasuryParentAccount(int $ownerId, string $actor): Account
    {
        return DB::transaction(function () use ($ownerId, $actor) {

            $cash = Account::query()
                ->where('user_id', $ownerId)
                ->where('account_number', '1100')
                ->first();

            if ($cash) {
                return $cash;
            }

            $assetTypeId = $this->assetTypeId($ownerId);

            $assets = $this->upsertAccount($ownerId, $actor, [
                'account_type_id' => $assetTypeId,
                'parent_account_id' => null,
                'name' => 'الأصول',
                'account_number' => '1000',
            ]);

            $currentAssets = $this->upsertAccount($ownerId, $actor, [
                'account_type_id' => $assetTypeId,
                'parent_account_id' => $assets->id,
                'name' => 'الأصول المتداولة',
                'account_number' => '1010',
            ]);

            return $this->upsertAccount($ownerId, $actor, [
                'account_type_id' => $assetTypeId,
                'parent_account_id' => $currentAssets->id,
                'name' => 'الصندوق',
                'account_number' => '1100',
            ]);
        });
    }

    private function nextTreasuryAccountNumber(int $ownerId, int $parentId): int
    {
        // لو MySQL: regexp شغال — لو SQLite أثناء tests ممكن يحتاج تعديل
        $last = Account::query()
            ->where('user_id', $ownerId)
            ->where('parent_account_id', $parentId)
            ->where('account_number', 'regexp', '^[0-9]+$')
            ->lockForUpdate()
            ->orderByRaw('CAST(account_number AS UNSIGNED) DESC')
            ->first();

        $nextNo = 1101;
        if ($last && is_numeric($last->account_number)) {
            $nextNo = ((int) $last->account_number) + 1;
        }

        return $nextNo;
    }

    private function createTreasuryAccount(int $ownerId, string $actor, string $treasuryName): Account
    {
        return DB::transaction(function () use ($ownerId, $actor, $treasuryName) {

            $parent = $this->treasuryParentAccount($ownerId, $actor);

            $wantedName = 'الصندوق - '.$treasuryName;

            $existing = Account::query()
                ->where('user_id', $ownerId)
                ->where('parent_account_id', $parent->id)
                ->where('name', $wantedName)
                ->first();

            if ($existing) {
                return $existing;
            }

            $nextNo = $this->nextTreasuryAccountNumber($ownerId, (int) $parent->id);

            return Account::create([
                'user_id' => $ownerId,
                'account_type_id' => $parent->account_type_id,
                'parent_account_id' => $parent->id,
                'name' => $wantedName,
                'account_number' => (string) $nextNo,
                'start_balance' => 0,
                'current_balance' => 0,
                'status' => true,
                'updated_by' => $actor,
            ]);
        });
    }

    private function makeCode(int $ownerId): string
    {
        return 'TR-'.$ownerId.'-'.Str::upper(Str::random(6));
    }

    public function run(): void
    {
        $owners = User::query()
            ->whereNull('owner_user_id')
            ->select('id', 'name')
            ->get();

        if ($owners->isEmpty()) {
            $this->command?->warn('No owners found, TreasurySeeder skipped.');

            return;
        }

        $today = now()->toDateString();

        foreach ($owners as $owner) {

            DB::transaction(function () use ($owner, $today) {

                $actor = $owner->name ?: 'system';

                // ✅ (1) الخزنة الرئيسية
                $master = Treasuries::updateOrCreate(
                    [
                        'user_id' => $owner->id,
                        'is_master' => true,
                    ],
                    [
                        'name' => 'الخزنة الرئيسية',
                        'code' => 'TR-MASTER',
                        'account_id' => null, // هنحدده تحت
                        'last_payment_receipt_no' => 0,
                        'last_collection_receipt_no' => 0,
                        'last_reconciled_at' => $today,
                        'status' => true,
                        'updated_by' => $actor,
                    ]
                );

                if (! $master->account_id) {
                    $acc = $this->createTreasuryAccount($owner->id, $actor, $master->name);
                    $master->update(['account_id' => $acc->id, 'updated_by' => $actor]);
                }

                // ✅ (2) خزنة فرعية
                $sub = Treasuries::updateOrCreate(
                    [
                        'user_id' => $owner->id,
                        'code' => 'TR-SUB',
                    ],
                    [
                        'name' => 'خزنة فرعية',
                        'is_master' => false,
                        'account_id' => null,
                        'last_payment_receipt_no' => 0,
                        'last_collection_receipt_no' => 0,
                        'last_reconciled_at' => $today,
                        'status' => true,
                        'updated_by' => $actor,
                    ]
                );

                if (! $sub->account_id) {
                    $acc = $this->createTreasuryAccount($owner->id, $actor, $sub->name);
                    $sub->update(['account_id' => $acc->id, 'updated_by' => $actor]);
                }

                // ✅ (3) خزنة المعرض
                $show = Treasuries::updateOrCreate(
                    [
                        'user_id' => $owner->id,
                        'code' => 'TR-SHOW',
                    ],
                    [
                        'name' => 'خزنة المعرض',
                        'is_master' => false,
                        'account_id' => null,
                        'last_payment_receipt_no' => 0,
                        'last_collection_receipt_no' => 0,
                        'last_reconciled_at' => $today,
                        'status' => true,
                        'updated_by' => $actor,
                    ]
                );

                if (! $show->account_id) {
                    $acc = $this->createTreasuryAccount($owner->id, $actor, $show->name);
                    $show->update(['account_id' => $acc->id, 'updated_by' => $actor]);
                }
            });
        }
    }
}
