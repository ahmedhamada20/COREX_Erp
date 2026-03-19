<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalance extends Model
{
    protected $table = 'account_balances';

    protected $fillable = [
        'user_id',
        'account_id',
        'currency_code',
        'branch_id',
        'debit_total',
        'credit_total',
        'balance',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'account_id' => 'integer',
        'branch_id' => 'integer',
        'debit_total' => 'decimal:2',
        'credit_total' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function scopeOwner($query, int $ownerId)
    {
        return $query->where('user_id', $ownerId);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
