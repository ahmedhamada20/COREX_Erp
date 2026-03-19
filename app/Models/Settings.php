<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settings extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
        'vat_number',
        'vat_rate',
        'fiscal_year_start',
        'base_currency',
        'invoice_prefix',
        'decimal_places',
        'enable_inventory_tracking',
        'logo',
        'favicon',
        'status',
        'general_alert',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'vat_rate' => 'float',
            'fiscal_year_start' => 'date',
            'decimal_places' => 'integer',
            'enable_inventory_tracking' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
