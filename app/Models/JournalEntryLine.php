<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'user_id', 'journal_entry_id', 'account_id',
        'cost_center_id', 'project_id', 'branch_id', 'warehouse_id',
        'debit', 'credit', 'currency_code', 'fx_rate', 'memo', 'line_no',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'journal_entry_id' => 'integer',
        'account_id' => 'integer',
        'cost_center_id' => 'integer',
        'project_id' => 'integer',
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'fx_rate' => 'decimal:6',
        'line_no' => 'integer',
    ];

    // Relationships
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    // Helpers
    public function isDebit(): bool
    {
        return (float) $this->debit > 0;
    }

    public function isCredit(): bool
    {
        return (float) $this->credit > 0;
    }

    public function amount(): float
    {
        return $this->isDebit() ? (float) $this->debit : (float) $this->credit;
    }
}
