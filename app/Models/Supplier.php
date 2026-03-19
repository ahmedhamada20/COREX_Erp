<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'supplier_category_id',
        'name',
        'code',
        'phone',
        'email',
        'image',
        'city',
        'account_number',
        'start_balance',
        'current_balance',
        'notes',
        'status',
        'date',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supplierCategory()
    {
        return $this->belongsTo(SupplierCategory::class, 'supplier_category_id');
    }
}
