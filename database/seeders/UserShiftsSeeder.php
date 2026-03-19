<?php

namespace Database\Seeders;

use App\Models\Treasuries;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Database\Seeder;

class UserShiftsSeeder extends Seeder
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

            // ✅ نختار خزنة مناسبة: master أولاً
            $treasury = Treasuries::query()
                ->where('user_id', $owner->id)
                ->orderByDesc('is_master')
                ->orderBy('id')
                ->first();

            if (! $treasury) {
                $this->command?->warn("Owner #{$owner->id} has no treasuries. TreasurySeeder first.");

                continue;
            }

            // ✅ actor_user_id: مؤقتًا نخليه owner نفسه

            $actorUserId = $owner->id;

            // ✅ لو فيه شفت open بالفعل على نفس الخزنة لنفس الـ actor، ما تعملش واحد جديد
            $existsOpen = UserShift::query()
                ->where('user_id', $owner->id)
                ->where('actor_user_id', $actorUserId)
                ->where('treasury_id', $treasury->id)
                ->where('status', 'open')
                ->exists();

            if ($existsOpen) {
                continue;
            }

            $openedAt = now()->subHours(random_int(1, 8));

            UserShift::create([
                'user_id' => $owner->id,
                'actor_user_id' => $actorUserId,
                'treasury_id' => $treasury->id,

                'opened_at' => $openedAt,
                'closed_at' => null,

                // إنت لسه مش شغال Cash Ops
                'opening_balance' => 0,
                'closing_expected' => 0,
                'closing_actual' => null,
                'difference' => 0,

                'status' => 'open',
                'closed_by' => null,
            ]);
        }
    }
}
