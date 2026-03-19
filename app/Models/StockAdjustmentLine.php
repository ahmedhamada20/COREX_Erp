<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentLine extends Model
{
    protected $fillable = [
        'stock_adjustment_id',
        'item_id',
        'store_id',
        'quantity_diff',
        'unit_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_diff' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'item_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Stores::class, 'store_id');
    }
}
