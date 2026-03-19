<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnItem extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_return_items';

    protected $fillable = [
        'user_id',
        'purchase_return_id',
        'item_id',
        'purchase_invoice_item_id',

        'warehouse_name_snapshot',
        'transaction_id',

        'quantity',
        'unit_price',

        'tax_rate',
        'tax_value',

        'line_subtotal',
        'line_total',

        'notes',
        'date',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_value' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_total' => 'decimal:2',
        'date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function item()
    {
        return $this->belongsTo(Items::class, 'item_id');
    }

    public function purchaseInvoiceItem()
    {
        return $this->belongsTo(PurchaseInvoiceItem::class, 'purchase_invoice_item_id');
    }

    public function scopeOwner($query, int $ownerId)
    {
        return $query->where('user_id', $ownerId);
    }
}
