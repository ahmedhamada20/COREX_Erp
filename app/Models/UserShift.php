<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserShift extends Model
{
    protected $table = 'user_shifts';

    protected $fillable = [
        'user_id',
        'actor_user_id',
        'treasury_id',
        'transaction_id',

        'opened_at',
        'closed_at',

        'opening_balance',
        'closing_expected',
        'closing_actual',
        'difference',

        'status',
        'closed_by',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_expected' => 'decimal:2',
        'closing_actual' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id'); // tenant/owner
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id'); // الموظف
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasuries::class, 'treasury_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(TreasuriesDelivery::class, 'shift_id');
    }

    /**
     * Scopes مفيدة
     */
    public function scopeOpen($q)
    {
        return $q->where('status', 'open');
    }

    public function scopeForOwner($q, int $ownerId)
    {
        return $q->where('user_id', $ownerId);
    }

    public function scopeForActor($q, int $actorUserId)
    {
        return $q->where('actor_user_id', $actorUserId);
    }
}
