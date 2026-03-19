<?php

namespace Database\Seeders;

use App\Models\SalesMaterialType;
use App\Models\User;
use Illuminate\Database\Seeder;

class SalesMaterialTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'مواد خام',
            'منتج تام',
            'منتج نصف مصنع',
            'خدمة',
            'قطع غيار',
            'مستلزمات تشغيل',
            'مواد تعبئة',
            'مواد تغليف',
            'إكسسوارات',
            'منتجات موسمية',
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
            foreach ($types as $typeName) {

                $createdAt = now()->subDays(random_int(0, 60));

                SalesMaterialType::updateOrCreate(
                    [
                        'user_id' => $owner->id,
                        'name' => $typeName,
                    ],
                    [
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
