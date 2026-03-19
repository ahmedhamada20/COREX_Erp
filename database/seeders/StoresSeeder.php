<?php

namespace Database\Seeders;

use App\Models\Stores;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoresSeeder extends Seeder
{
    public function run(): void
    {
        $storeNames = [
            'المخزن الرئيسي',
            'مخزن الفرع الأول',
            'مخزن الفرع الثاني',
            'مخزن المعرض',
            'مخزن التوريدات',
            'مخزن المرتجعات',
            'مخزن قطع الغيار',
            'مخزن المواد الخام',
            'مخزن المنتجات التامة',
            'مخزن التعبئة والتغليف',
        ];

        // ✅ Owners فقط
        $owners = User::query()
            ->whereNull('owner_user_id')
            ->select('id', 'name')
            ->get();

        if ($owners->isEmpty()) {
            $this->command?->warn('No owners found. Seed users first.');

            return;
        }

        foreach ($owners as $owner) {

            $count = random_int(5, 10);

            $names = collect($storeNames)
                ->shuffle()
                ->take($count);

            foreach ($names as $name) {

                $createdAt = now()->subDays(random_int(0, 90));

                Stores::updateOrCreate(
                    [
                        'user_id' => $owner->id,
                        'name' => $name,
                    ],
                    [
                        'address' => fake()->address(),
                        'phone' => fake()->phoneNumber(),
                        'date' => $createdAt->toDateString(),
                        'status' => (bool) random_int(0, 1),
                        'updated_by' => $owner->name ?: 'system',
                        'created_at' => $createdAt,
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
