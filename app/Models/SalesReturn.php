<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $table = 'sales_returns';

    protected $fillable = [
        'user_id',
        'customer_id',
        'sales_invoice_id',
        'return_date',
        'subtotal',
        'vat_amount',
        'total',
        'journal_entry_id',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:4',
        'vat_amount' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function scopeMine($q)
    {
        $u = auth()->user();
        $ownerId = (int) ($u->owner_user_id ?? $u->id);

        return $q->where('user_id', $ownerId);
    }
}
