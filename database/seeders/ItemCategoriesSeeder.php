<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ItemCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'إلكترونيات',
            'أجهزة منزلية',
            'ملابس',
            'أحذية',
            'مستلزمات مكتبية',
            'مواد غذائية',
            'مستحضرات تجميل',
            'قطع غيار',
            'معدات صناعية',
            'إكسسوارات',
            'منتجات رقمية',
            'أدوات رياضية',
        ];

        $owners = User::query()
            ->whereNull('owner_user_id')
            ->select('id', 'name')
            ->get();

        if ($owners->isEmpty()) {
            $this->command?->warn('No owners found. Seed users first.');

            return;
        }

        foreach ($owners as $owner) {

            $count = random_int(6, 10);

            $selected = collect($categories)
                ->shuffle()
                ->take($count);

            foreach ($selected as $name) {

                $createdAt = now()->subDays(random_int(0, 90));
                $date = $createdAt->toDateString();

                ItemCategory::updateOrCreate(
                    [
                        'user_id' => $owner->id,
                        'name' => $name,
                    ],
                    [
                        'address' => fake()->address(),
                        'phone' => fake()->phoneNumber(),
                        'date' => $date,
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
