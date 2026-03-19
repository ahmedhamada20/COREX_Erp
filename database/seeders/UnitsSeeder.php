<?php

namespace Database\Seeders;

use App\Models\Units;
use App\Models\User;
use Illuminate\Database\Seeder;

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        $branchNames = [
            'الفرع الرئيسي',
            'فرع القاهرة',
            'فرع الجيزة',
            'فرع الإسكندرية',
            'فرع أكتوبر',
            'فرع المنصورة',
            'فرع طنطا',
            'فرع أسيوط',
            'فرع السويس',
            'فرع الزقازيق',
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

            $count = random_int(3, 6);

            // ✅ نخلي "الفرع الرئيسي" أولاً غالبًا (لو موجود)
            $names = collect($branchNames)->shuffle()->take($count)->values();
            if ($names->contains('الفرع الرئيسي')) {
                $names = collect(['الفرع الرئيسي'])
                    ->merge($names->reject(fn ($n) => $n === 'الفرع الرئيسي'))
                    ->values();
            }

            foreach ($names as $index => $name) {

                $createdAt = now()->subDays(random_int(0, 60));

                Units::updateOrCreate(
                    [
                        'user_id' => $owner->id,
                        'name' => $name,
                    ],
                    [
                        'is_master' => $index === 0 ? 1 : 0,
                        'address' => fake()->address(),
                        'phone' => fake()->phoneNumber(),
                        'date' => $createdAt->toDateString(),
                        'status' => true,
                        'updated_by' => $owner->name ?: 'system',
                        'created_at' => $createdAt,
                        'updated_at' => now(),
                    ]
                );
            }

            Units::updateOrCreate(
                [
                    'user_id' => $owner->id,
                    'name' => 'الفرع الرئيسي',
                ],
                [
                    'is_master' => 1,
                    'address' => fake()->address(),
                    'phone' => fake()->phoneNumber(),
                    'date' => now()->toDateString(),
                    'status' => true,
                    'updated_by' => $owner->name ?: 'system',
                    'updated_at' => now(),
                ]
            );
        }
    }
}
