<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_invoices';

    protected $fillable = [
        'user_id',
        'supplier_id',

        'purchase_invoice_code',
        'invoice_number',
        'invoice_date',

        'transaction_id',
        'purchase_order_id',

        'payment_type',
        'due_date',

        'currency_code',
        'exchange_rate',
        'tax_included',

        'subtotal_before_discount',

        'discount_type',
        'discount_rate',
        'discount_value',

        'shipping_cost',
        'other_charges',

        'subtotal',
        'tax_value',
        'total',

        'paid_amount',
        'remaining_amount',

        'status',
        'posted_at',
        'posted_by',

        'notes',
        'date',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'posted_at' => 'datetime',
        'date' => 'date',

        'tax_included' => 'boolean',

        'exchange_rate' => 'decimal:6',

        'subtotal_before_discount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_value' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    // =========================
    // Relationships
    // =========================

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'purchase_invoice_id');
    }

    /**
     * Optional: if you created purchase_payments table/model.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class, 'purchase_invoice_id');
    }

    /**
     * Optional: returns linked to this invoice (if you added purchase_invoice_id on purchase_returns).
     */
    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_invoice_id');
    }

    // =========================
    // Scopes
    // =========================

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('user_id', $ownerId);
    }

    public function scopePosted($query)
    {
        return $query->whereIn('status', ['posted', 'paid', 'partial']);
    }

    // =========================
    // Helpers / Accessors
    // =========================

    public function getIsPostedAttribute(): bool
    {
        return in_array($this->status, ['posted', 'paid', 'partial'], true);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->payment_type === 'credit';
    }

    public function getIsCashAttribute(): bool
    {
        return $this->payment_type === 'cash';
    }

    public function balanceImpact(): float
    {
        return (float) $this->total - (float) $this->paid_amount;
    }

    public function journalEntry()
    {

        return $this->hasOne(JournalEntry::class, 'reference_id', 'id')
            ->where('user_id', $this->user_id)
            ->where('source', 'purchase')
            ->where('reference_type', self::class)
            ->where('reference_id', $this->id);
    }
}
