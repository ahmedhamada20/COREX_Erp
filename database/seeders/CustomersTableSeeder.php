<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomersTableSeeder extends Seeder
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

                $assets = Account::query()
                    ->where('user_id', $owner->id)
                    ->where('account_number', '1000')
                    ->first();

                if (! $assets) {
                    $this->command?->warn("Owner #{$owner->id}: Missing account 1000 (الأصول). Run AccountSeeder first.");

                    return;
                }

                $currentAssets = Account::query()
                    ->where('user_id', $owner->id)
                    ->where('account_number', '1100')
                    ->first();

                if (! $currentAssets) {
                    $this->command?->warn("Owner #{$owner->id}: Missing account 1100 (الأصول المتداولة). Run AccountSeeder first.");

                    return;
                }

                $customersParent = Account::query()
                    ->where('user_id', $owner->id)
                    ->where('account_number', '1120') // ✅ ثابت
                    ->first();

                if (! $customersParent) {
                    $this->command?->warn("Owner #{$owner->id}: Missing account 1120 (العملاء). Run AccountSeeder first.");

                    return;
                }

                for ($i = 1; $i <= 30; $i++) {

                    $code = 'CUST-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT);

                    $balanceTypes = [0, random_int(100, 5000), -random_int(100, 5000)];
                    $startBalance = (float) $balanceTypes[array_rand($balanceTypes)];

                    // ✅ Customer upsert (fixed code)
                    $customer = Customer::updateOrCreate(
                        [
                            'user_id' => $owner->id,
                            'code' => $code,
                        ],
                        [
                            'name' => 'عميل '.$i,
                            'phone' => '01'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                            'email' => "customer{$i}@{$owner->id}.demo.com",
                            'city' => $cities[array_rand($cities)],
                            'start_balance' => $startBalance,
                            'current_balance' => $startBalance,
                            'notes' => 'عميل تجريبي للنظام',
                            'status' => true,
                            'date' => now()->toDateString(),
                            'updated_by' => $owner->name,
                        ]
                    );

                    $otherId = "customers:{$customer->id}";

                    $account = Account::updateOrCreate(
                        [
                            'user_id' => $owner->id,
                            'other_table_id' => $otherId, // ✅ مفتاح الربط
                        ],
                        [
                            'account_type_id' => $customersParent->account_type_id,
                            'parent_account_id' => $customersParent->id,
                            'name' => "عميل: {$customer->name} ({$customer->code})",
                            'account_number' => $this->accountNumberForExistingOrNext($owner->id, $otherId),
                            'start_balance' => $startBalance,
                            'current_balance' => $this->keepExistingBalanceOrStart($owner->id, $otherId, $startBalance),
                            'status' => true,
                            'date' => now()->toDateString(),
                            'updated_by' => $owner->name,
                        ]
                    );

                    // ✅ Sync customer.account_number from account
                    $customer->update([
                        'account_number' => $account->account_number,
                    ]);
                }
            });
        }

        $this->command?->info('Customers seeded successfully with accounts under chart (1120).');
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

    private function nextAccountNumber(int $ownerId): string
    {
        $max = Account::query()
            ->where('user_id', $ownerId)
            ->where('account_number', 'like', 'ACC-%')
            ->selectRaw('MAX(CAST(SUBSTRING(account_number, 5) AS UNSIGNED)) as max_no')
            ->value('max_no');

        $next = ((int) ($max ?? 0)) + 1;

        return 'ACC-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
