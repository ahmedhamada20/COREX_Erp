<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $password = Hash::make('password');
        $now = now();

        $ownersCount = 2;
        $employeesEach = 1000;
        $chunkSize = 500;

        for ($i = 1; $i <= $ownersCount; $i++) {

            // ========= Owner =========
            $owner = User::updateOrCreate(
                ['email' => "owner{$i}@demo.com"],
                [
                    'name' => "Demo Owner {$i}",
                    'password' => $password,
                    'owner_user_id' => null,
                ]
            );

            $this->command?->info("Owner #{$i} => {$owner->email} | employees: {$employeesEach}");

            $batch = [];
            $inserted = 0;

            for ($j = 1; $j <= $employeesEach; $j++) {

                $batch[] = [
                    'name' => "Employee {$j} (Owner {$i})",
                    'email' => "owner{$i}.emp{$j}@demo.com",
                    'password' => $password,
                    'owner_user_id' => $owner->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $chunkSize) {
                    // ✅ Upsert by email (unique)
                    DB::table('users')->upsert(
                        $batch,
                        ['email'],
                        ['name', 'password', 'owner_user_id', 'updated_at']
                    );

                    $inserted += count($batch);
                    $batch = [];

                    if ($inserted % 20000 === 0) {
                        $this->command?->line("  - inserted/updated: {$inserted} / {$employeesEach}");
                    }
                }
            }

            if (! empty($batch)) {
                DB::table('users')->upsert(
                    $batch,
                    ['email'],
                    ['name', 'password', 'owner_user_id', 'updated_at']
                );
            }

            $this->command?->info("Done owner #{$i}");
        }

        $this->command?->info('UsersSeeder finished.');
    }
}
