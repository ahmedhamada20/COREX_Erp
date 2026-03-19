<?php

namespace App\Services\Inventory;

use App\Models\StockMovement;

class StockMovementRecorder
{
    /**
     * @param array{
     *     user_id: int,
     *     item_id: int,
     *     store_id: int|null,
     *     movement_type: string,
     *     quantity: float,
     *     unit_cost: float,
     *     reference_type: string|null,
     *     reference_id: int|null,
     *     notes: string|null
     * } $data
     */
    public function record(array $data): StockMovement
    {
        return StockMovement::create([
            'user_id' => $data['user_id'],
            'item_id' => $data['item_id'],
            'store_id' => $data['store_id'] ?? null,
            'movement_type' => $data['movement_type'],
            'quantity' => abs((float) $data['quantity']),
            'unit_cost' => (float) ($data['unit_cost'] ?? 0),
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->user()?->name ?? 'system',
        ]);
    }

    /**
     * Record multiple movements for an invoice's items at once.
     *
     * @param  iterable<\App\Models\SalesInvoiceItem|\App\Models\PurchaseInvoiceItem>  $items
     */
    public function recordForInvoiceItems(
        int $userId,
        string $movementType,
        string $referenceType,
        int $referenceId,
        iterable $items,
        ?int $storeId = null
    ): void {
        foreach ($items as $item) {
            $this->record([
                'user_id' => $userId,
                'item_id' => $item->item_id,
                'store_id' => $item->store_id ?? $storeId,
                'movement_type' => $movementType,
                'quantity' => $item->quantity,
                'unit_cost' => $item->cost_price ?? $item->unit_price ?? 0,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => null,
            ]);
        }
    }
}
