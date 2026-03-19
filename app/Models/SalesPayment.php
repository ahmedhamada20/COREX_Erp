<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPayment extends Model
{
    protected $table = 'sales_payments';

    protected $fillable = [
        'sales_invoice_id',
        'treasury_id',
        'terminal_id',
        'amount',
        'payment_date',
        'reference',
        'journal_entry_id',
        'method',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'payment_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function treasury()
    {
        return $this->belongsTo(Treasuries::class, 'treasury_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
