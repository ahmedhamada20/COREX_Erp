<?php

namespace App\Services\Suppliers;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class CreatePurchaseOrder
{
    /**
     * @param array{supplier_id:int,order_date:string,expected_date?:string|null,status?:string|null,tax_amount?:float|int|string|null,notes?:string|null,items:array<int,array<string,mixed>>} $data
     */
    public function handle(int $tenantId, string $actorName, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($tenantId, $actorName, $data): PurchaseOrder {
            $subtotal = 0.0;
            $lines = [];

            foreach ($data['items'] as $row) {
                $quantity = round((float) $row['quantity'], 4);
                $unitPrice = round((float) $row['unit_price'], 4);
                $lineTotal = round($quantity * $unitPrice, 4);
                $subtotal += $lineTotal;

                $lines[] = [
                    'item_id' => (int) $row['item_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'notes' => $row['notes'] ?? null,
                ];
            }

            $taxAmount = round((float) ($data['tax_amount'] ?? 0), 2);

            $order = PurchaseOrder::create([
                'user_id' => $tenantId,
                'supplier_id' => (int) $data['supplier_id'],
                'order_number' => $this->nextOrderNumber($tenantId),
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'subtotal' => round($subtotal, 2),
                'tax_amount' => $taxAmount,
                'total' => round($subtotal + $taxAmount, 2),
                'notes' => $data['notes'] ?? null,
                'updated_by' => $actorName,
            ]);

            $order->items()->createMany($lines);

            return $order;
        });
    }

    private function nextOrderNumber(int $tenantId): string
    {
        $prefix = 'PO-'.now()->format('Y').'-';

        $last = PurchaseOrder::query()
            ->where('user_id', $tenantId)
            ->where('order_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('order_number');

        $lastNumber = 0;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return $prefix.str_pad((string) ($lastNumber + 1), 6, '0', STR_PAD_LEFT);
    }
}

