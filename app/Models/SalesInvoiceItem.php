<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    protected $table = 'sales_invoice_items';

    protected $fillable = [
        'sales_invoice_id',
        'item_id',
        'quantity',
        'price',
        'discount',
        'vat',
        'total',
        'cost_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'price' => 'decimal:4',
        'discount' => 'decimal:4',
        'vat' => 'decimal:4',
        'total' => 'decimal:4',
        'cost_price' => 'decimal:4',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function item()
    {
        return $this->belongsTo(Items::class, 'item_id'); // عندك اسم الموديل Items في مشروعك
    }
}
