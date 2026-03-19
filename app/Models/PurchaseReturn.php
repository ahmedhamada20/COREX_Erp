<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_returns';

    protected $fillable = [
        'user_id',

        // روابط
        'supplier_id',
        'purchase_invoice_id',

        // بيانات المرتجع
        'purchase_return_code',
        'return_number',
        'return_date',
        'transaction_id',

        // إجماليات
        'subtotal',
        'tax_value',
        'total',

        // حالة
        'status',
        'posted_at',
        'posted_by',

        // ملاحظات و meta
        'notes',
        'date',
        'updated_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'posted_at' => 'datetime',
        'date' => 'date',

        'subtotal' => 'decimal:2',
        'tax_value' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // =========================
    // Relations
    // =========================

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_return_id');
    }

    public function journalEntry()
    {
        return $this->hasOne(JournalEntry::class, 'reference_id', 'id')
            ->where('source', 'purchase')
            ->where('reference_type', self::class);
    }

    public function scopeOwner($q, int $ownerId)
    {
        return $q->where('user_id', $ownerId);
    }

    public function scopePosted($q)
    {
        return $q->where('status', 'posted');
    }

    public function scopeDraft($q)
    {
        return $q->where('status', 'draft');
    }
}
