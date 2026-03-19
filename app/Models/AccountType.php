<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountType extends Model
{
    use HasFactory ,SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'code',
        'status',
        'date',
        'updated_by',
        'allow_posting',
        'normal_side',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => 'boolean',
        'allow_posting' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }
}
