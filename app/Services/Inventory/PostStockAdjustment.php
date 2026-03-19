<?php

namespace App\Services\Inventory;

use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;

class PostStockAdjustment
{
    /**
     * @param array{adjustment_date:string,notes?:string|null,lines:array<int,array<string,mixed>>} $data
     */
    public function handle(int $tenantId, string $actorName, array $data): StockAdjustment
    {
        return DB::transaction(function () use ($tenantId, $actorName, $data): StockAdjustment {
            $adjustment = StockAdjustment::create([
                'user_id' => $tenantId,
                'adjustment_number' => $this->nextNumber($tenantId),
                'adjustment_date' => $data['adjustment_date'],
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'updated_by' => $actorName,
            ]);

            $recorder = app(StockMovementRecorder::class);

            foreach ($data['lines'] as $row) {
                $quantityDiff = round((float) $row['quantity_diff'], 4);
                $movementType = $quantityDiff >= 0 ? 'adjustment_in' : 'adjustment_out';

                $line = $adjustment->lines()->create([
                    'item_id' => (int) $row['item_id'],
                    'store_id' => ! empty($row['store_id']) ? (int) $row['store_id'] : null,
                    'quantity_diff' => $quantityDiff,
                    'unit_cost' => round((float) ($row['unit_cost'] ?? 0), 4),
                    'notes' => $row['notes'] ?? null,
                ]);

                $recorder->record([
                    'user_id' => $tenantId,
                    'item_id' => $line->item_id,
                    'store_id' => $line->store_id,
                    'movement_type' => $movementType,
                    'quantity' => abs($quantityDiff),
                    'unit_cost' => (float) $line->unit_cost,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'notes' => $line->notes,
                ]);
            }

            return $adjustment;
        });
    }

    private function nextNumber(int $tenantId): string
    {
        $prefix = 'SA-'.now()->format('Y').'-';

        $last = StockAdjustment::query()
            ->where('user_id', $tenantId)
            ->where('adjustment_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('adjustment_number');

        $lastNumber = 0;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return $prefix.str_pad((string) ($lastNumber + 1), 6, '0', STR_PAD_LEFT);
    }
}

