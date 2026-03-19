<?php

namespace Database\Seeders;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $owners = User::query()
            ->whereNull('owner_user_id')
            ->select('id', 'name')
            ->get();

        if ($owners->isEmpty()) {
            $this->command?->warn('No owners found. Seed users first.');

            return;
        }

        foreach ($owners as $owner) {

            Settings::updateOrCreate(
                ['user_id' => $owner->id], // unique per company
                [
                    'name' => $owner->name,
                    'phone' => fake()->phoneNumber(),
                    'address' => fake()->address(),
                    'logo' => null,
                    'favicon' => null,
                    'status' => true,
                    'general_alert' => 'مرحبًا بك في نظام COREX',
                    'updated_by' => $owner->name ?? 'system',
                ]
            );
        }
    }
}
