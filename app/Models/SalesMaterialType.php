<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesMaterialType extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'date',
        'status',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
