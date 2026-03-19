<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountTypesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $owners = User::query()
            ->whereNull('owner_user_id')
            ->select('id', 'name')
            ->get();

        if ($owners->isEmpty()) {
            $this->command?->warn('No owners found. Seed users first.');

            return;
        }

        $defaults = [
            ['name' => 'الأصول',        'code' => 'AST', 'status' => true, 'allow_posting' => false, 'normal_side' => 'debit'],
            ['name' => 'الخصوم',        'code' => 'LIA', 'status' => true, 'allow_posting' => false, 'normal_side' => 'credit'],
            ['name' => 'حقوق الملكية',  'code' => 'EQT', 'status' => true, 'allow_posting' => false, 'normal_side' => 'credit'],
            ['name' => 'الإيرادات',     'code' => 'REV', 'status' => true, 'allow_posting' => false, 'normal_side' => 'credit'],
            ['name' => 'المصروفات',     'code' => 'EXP', 'status' => true, 'allow_posting' => false, 'normal_side' => 'debit'],
            ['name' => 'تكلفة المبيعات', 'code' => 'COG', 'status' => true, 'allow_posting' => false, 'normal_side' => 'debit'],
            ['name' => 'أخرى',          'code' => 'OTH', 'status' => true, 'allow_posting' => false, 'normal_side' => 'debit'],
        ];

        foreach ($owners as $owner) {
            foreach ($defaults as $item) {

                DB::table('account_types')->updateOrInsert(

                    ['user_id' => $owner->id, 'code' => $item['code']],

                    [
                        'user_id' => $owner->id,
                        'name' => $item['name'],
                        'code' => $item['code'],
                        'status' => (bool) $item['status'],
                        'allow_posting' => (bool) $item['allow_posting'],
                        'normal_side' => $item['normal_side'] ?? 'debit',
                        'date' => $now->toDateString(),
                        'updated_by' => $owner->name ?: 'system',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
