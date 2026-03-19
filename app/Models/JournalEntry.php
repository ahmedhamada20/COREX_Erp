<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JournalEntry extends Model
{
    protected $table = 'journal_entries';

    protected $fillable = [
        'user_id',
        'entry_number',
        'entry_date',
        'source',
        'reference_type',
        'reference_id',
        'description',
        'total_debit',
        'total_credit',
        'status',
        'posted_at',
        'posted_by',
        'reversed_entry_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'reference_id' => 'integer',
        'user_id' => 'integer',
        'posted_by' => 'integer',
        'reversed_entry_id' => 'integer',
    ];

    // Relationships
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }

    public function reversedEntry(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'reversed_entry_id');
    }

    // Scopes
    public function scopeOwner($query, int $ownerId)
    {
        return $query->where('user_id', $ownerId);
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    // Helpers
    public function isBalanced(): bool
    {
        return round((float) $this->total_debit, 2) === round((float) $this->total_credit, 2);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }
}
