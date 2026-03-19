<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $itemName,
        public readonly float $currentQuantity,
        public readonly float $reorderPoint,
        public readonly int $itemId,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'item_id' => $this->itemId,
            'item_name' => $this->itemName,
            'current_quantity' => $this->currentQuantity,
            'reorder_point' => $this->reorderPoint,
            'message' => "تنبيه: مخزون الصنف \"{$this->itemName}\" وصل للحد الأدنى ({$this->currentQuantity} وحدة متبقية)",
        ];
    }
}
