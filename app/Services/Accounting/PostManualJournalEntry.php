<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;

class PostManualJournalEntry
{
    /**
     * @param array{entry_date: string, description?: string|null, lines: array<int, array<string, mixed>>} $data
     */
    public function handle(int $tenantId, int $actorUserId, array $data): JournalEntry
    {
        $entryDate = new \DateTimeImmutable((string) $data['entry_date']);

        $payload = [
            'user_id' => $tenantId,
            'entry_number' => app(JournalEntryNumberGenerator::class)->next($tenantId, $entryDate),
            'entry_date' => $entryDate->format('Y-m-d'),
            'source' => 'manual',
            'reference_type' => null,
            'reference_id' => null,
            'description' => $data['description'] ?? null,
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $actorUserId,
        ];

        return app(LedgerWriter::class)->postEntry($payload, $data['lines']);
    }
}

