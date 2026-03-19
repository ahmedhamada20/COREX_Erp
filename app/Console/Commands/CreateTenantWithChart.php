<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Tenants\SeedChartOfAccounts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateTenantWithChart extends Command
{
    protected $signature = 'corex:tenant:create';

    protected $description = 'Create new owner (tenant) and seed chart of accounts for him';

    public function handle(): int
    {
        $name = 'Ahmed Hamada';
        $email = '12212222@gmail.com';
        $password = 'password';

        $owner = DB::transaction(function () use ($name, $email, $password) {

            // ✅ Create / Update owner (tenant)
            $owner = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'owner_user_id' => null,
                ]
            );

            // ✅ Seed chart for this owner only
            if ($owner) {
                app(SeedChartOfAccounts::class)->seedForOwnerId($owner->id);
            }

            return $owner;
        });

        $this->info("Owner ready: id={$owner->id}, email={$owner->email}, company_code={$owner->company_code}");
        if ($owner) {
            $this->info("Chart of accounts seeded for owner #{$owner->id}");
        }

        return self::SUCCESS;
    }

    private function makeCompanyCode(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $name));
        $base = substr($base, 0, 6) ?: 'COMP';

        return $base.'-'.random_int(1000, 9999);
    }
}
