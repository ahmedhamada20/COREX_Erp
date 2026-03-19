<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuppliersTableSeeder extends Seeder
{
    public function run(): void
    {
        $owners = User::whereNull('owner_user_id')->orderBy('id')->get();

        if ($owners->isEmpty()) {
            $this->command?->warn('No owners found. Seed UsersSeeder first.');

            return;
        }

        $cities = ['القاهرة', 'الجيزة', 'الإسكندرية', 'المنصورة', 'طنطا', 'الزقازيق'];

        foreach ($owners as $owner) {

            DB::transaction(function () use ($owner, $cities) {

                $categoryId = SupplierCategory::query()
                    ->where('user_id', $owner->id)
                    ->orderBy('id')
                    ->value('id');

                if (! $categoryId) {
                    $this->command?->warn("No SupplierCategories found for owner #{$owner->id}. Seed SupplierCategoriesSeeder first.");

                    return;
                }

                $suppliersParent = Account::query()
                    ->where('user_id', $owner->id)
                    ->where('account_number', '2100') // ✅ ثابت
                    ->lockForUpdate()
                    ->first();

                if (! $suppliersParent) {
                    $this->command?->warn("Owner #{$owner->id}: Missing account 2100 (الموردين). Run AccountSeeder first.");

                    return;
                }

                for ($i = 1; $i <= 30; $i++) {

                    $code = 'SUP-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT);

                    $balanceTypes = [0, random_int(100, 5000), -random_int(100, 5000)];
                    $startBalance = (float) $balanceTypes[array_rand($balanceTypes)];

                    // ✅ Supplier upsert (no duplicates)
                    $supplier = Supplier::updateOrCreate(
                        [
                            'user_id' => $owner->id,
                            'code' => $code,
                        ],
                        [
                            'supplier_category_id' => $categoryId,
                            'name' => 'مورد '.$i,
                            'phone' => '01'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                            'email' => "supplier{$i}@{$owner->id}.demo.com",
                            'city' => $cities[array_rand($cities)],
                            'start_balance' => $startBalance,
                            'current_balance' => $startBalance,
                            'notes' => 'مورد تجريبي للنظام',
                            'status' => true,
                            'date' => now()->toDateString(),
                            'updated_by' => $owner->name,
                        ]
                    );

                    $otherId = "suppliers:{$supplier->id}";

                    $account = Account::updateOrCreate(
                        [
                            'user_id' => $owner->id,
                            'other_table_id' => $otherId,
                        ],
                        [
                            'account_type_id' => $suppliersParent->account_type_id,
                            'parent_account_id' => $suppliersParent->id,
                            'name' => "مورد: {$supplier->name} ({$supplier->code})",

                            'account_number' => $this->accountNumberForExistingOrNext($owner->id, $otherId),

                            'start_balance' => $startBalance,

                            'current_balance' => $this->keepExistingBalanceOrStart($owner->id, $otherId, $startBalance),

                            'status' => true,
                            'date' => $supplier->date ?? now()->toDateString(),
                            'updated_by' => $owner->name,
                            'notes' => 'Supplier sub-account under A/P Control',
                        ]
                    );

                    $supplier->update([
                        'account_number' => $account->account_number,
                    ]);
                }
            });
        }

        $this->command?->info('Suppliers seeded successfully under chart: 2000 -> 2010 -> 2100');
    }

    private function accountNumberForExistingOrNext(int $ownerId, string $otherId): string
    {
        $existing = Account::query()
            ->where('user_id', $ownerId)
            ->where('other_table_id', $otherId)
            ->value('account_number');

        if ($existing) {
            return $existing;
        }

        return $this->nextAccountNumber($ownerId);
    }

    private function keepExistingBalanceOrStart(int $ownerId, string $otherId, float $startBalance): float
    {
        $existing = Account::query()
            ->where('user_id', $ownerId)
            ->where('other_table_id', $otherId)
            ->value('current_balance');

        return $existing !== null ? (float) $existing : $startBalance;
    }

    private function nextAccountNumber(int $tenantId): string
    {

        $max = Account::query()
            ->where('user_id', $tenantId)
            ->where('account_number', 'like', 'ACC-%')
            ->selectRaw('MAX(CAST(SUBSTRING(account_number, 5) AS UNSIGNED)) as max_no')
            ->lockForUpdate()
            ->value('max_no');

        $next = ((int) ($max ?? 0)) + 1;

        return 'ACC-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
