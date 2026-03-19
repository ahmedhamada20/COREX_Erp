<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;

class JournalEntryNumberGenerator
{
    public function next(int $userId, ?\DateTimeInterface $date = null): string
    {
        $year = ($date ? (int) $date->format('Y') : (int) now()->format('Y'));
        $prefix = "JE-{$year}-";

        // ⚠️ لو عندك ملايين قيود: الأفضل تعمل جدول sequences
        // ده حل عملي باستخدام lock
        return DB::transaction(function () use ($userId, $prefix) {

            $last = \App\Models\JournalEntry::where('user_id', $userId)
                ->where('entry_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('entry_number');

            $lastNum = 0;
            if ($last) {
                $digits = (int) preg_replace('/\D+/', '', str_replace($prefix, '', $last));
                $lastNum = $digits;
            }

            $next = $lastNum + 1;

            return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        });
    }
}
