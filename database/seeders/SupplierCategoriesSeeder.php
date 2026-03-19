<?php

namespace Database\Seeders;

use App\Models\SupplierCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupplierCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $owners = User::whereNull('owner_user_id')->get();

        if ($owners->isEmpty()) {
            $this->command->warn('No owners found.');

            return;
        }

        $categories = [
            'مواد خام',
            'موردين محليين',
            'موردين خارجيين',
            'موردين خدمات',
            'موردين نقل',
            'موردين معدات',
        ];

        foreach ($owners as $owner) {

            foreach ($categories as $category) {

                SupplierCategory::withTrashed()->updateOrCreate(
                    [
                        'user_id' => $owner->id,
                        'name' => $category,
                    ],
                    [
                        'status' => true,
                        'date' => now()->toDateString(),
                        'updated_by' => 'System Seeder',
                        'deleted_at' => null,
                    ]
                );
            }
        }

        $this->command->info('Supplier categories seeded successfully.');
    }
}
