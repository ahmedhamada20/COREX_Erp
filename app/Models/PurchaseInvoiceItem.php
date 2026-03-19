<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoiceItem extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_invoice_items';

    protected $fillable = [
        'user_id',
        'purchase_invoice_id',
        'item_id',

        'warehouse_name_snapshot',
        'transaction_id',

        'quantity',
        'unit_price',

        'discount_type',
        'discount_rate',
        'discount_value',

        'tax_rate',
        'tax_value',

        'line_subtotal',
        'line_total',

        'date',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',

        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',

        'discount_rate' => 'decimal:2',
        'discount_value' => 'decimal:2',

        'tax_rate' => 'decimal:2',
        'tax_value' => 'decimal:2',

        'line_subtotal' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
