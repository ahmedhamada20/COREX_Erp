<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treasuries extends Model
{
    protected $table = 'treasuries';

    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'code',
        'is_master',
        'last_payment_receipt_no',
        'last_collection_receipt_no',
        'last_reconciled_at',
        'status',
        'updated_by',
    ];

    protected $casts = [
        'is_master' => 'boolean',
        'status' => 'boolean',
        'last_reconciled_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(UserShift::class, 'treasury_id');
    }

    public function deliveriesFrom(): HasMany
    {
        return $this->hasMany(TreasuriesDelivery::class, 'from_treasury_id');
    }

    public function deliveriesTo(): HasMany
    {
        return $this->hasMany(TreasuriesDelivery::class, 'to_treasury_id');
    }

    // داخل Treasuries model
    public function scopeOwner($query, int $ownerId)
    {
        return $query->where('user_id', $ownerId);
    }

    public function getAccountIdOrFail(): int
    {
        return (int) ($this->account_id ?? 0);
    }
}
