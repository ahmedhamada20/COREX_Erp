<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuriesDelivery extends Model
{
    protected $table = 'treasuries_deliveries';

    protected $fillable = [
        'user_id',
        'actor_user_id',
        'counterparty_account_id',
        'journal_entry_id',
        'shift_id',

        'type',
        'from_treasury_id',
        'to_treasury_id',

        'amount',
        'receipt_no',
        'doc_date',

        'reason',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'doc_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(UserShift::class, 'shift_id');
    }

    public function fromTreasury(): BelongsTo
    {
        return $this->belongsTo(Treasuries::class, 'from_treasury_id');
    }

    public function toTreasury(): BelongsTo
    {
        return $this->belongsTo(Treasuries::class, 'to_treasury_id');
    }

    /**
     * Scopes مفيدة
     */
    public function scopeForOwner($q, int $ownerId)
    {
        return $q->where('user_id', $ownerId);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntrie::class, 'journal_entry_id');
    }

    public function counterpartyAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'counterparty_account_id');
    }
}
