<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_invoices';

    protected $fillable = [
        'user_id',
        'customer_id',
        'invoice_number',
        'invoice_code',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'vat_amount',
        'total',
        'paid_amount',
        'remaining_amount',
        'status',
        'payment_type',
        'journal_entry_id',
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

        'subtotal' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'vat_amount' => 'decimal:4',
        'total' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'remaining_amount' => 'decimal:4',
    ];

    // =========================
    // Relations
    // =========================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class, 'sales_invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(SalesPayment::class, 'sales_invoice_id');
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class, 'sales_invoice_id');
    }

    // =========================
    // Scopes (Tenant + Filters)
    // =========================
    public function scopeMine($q)
    {
        $u = auth()->user();
        $ownerId = (int) ($u->owner_user_id ?? $u->id);

        return $q->where('user_id', $ownerId);
    }

    public function scopePosted($q)
    {
        return $q->where('status', 'posted');
    }

    public function scopeNotCancelled($q)
    {
        return $q->where('status', '!=', 'cancelled');
    }

    // =========================
    // Helpers
    // =========================
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function refreshPaymentStatus(): void
    {
        // Update paid_amount & remaining_amount based on payments table
        $paid = (float) $this->payments()->sum('amount');
        $total = (float) $this->total;

        $remaining = max(0, $total - $paid);

        $status = $this->status;
        if ($this->status !== 'cancelled') {
            if ($paid <= 0) {
                // keep draft/posted as-is, don't force it
                // but if it's already posted and unpaid => keep posted
            } elseif ($remaining <= 0.0001) {
                $status = 'paid';
            } else {
                $status = 'partial';
            }
        }

        $this->forceFill([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $status,
        ])->save();
    }
}
