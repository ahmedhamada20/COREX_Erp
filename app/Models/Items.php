<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Items extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'items_code',
        'barcode',
        'name',
        'type',
        'item_category_id',
        'item_id',
        'does_has_retail_unit',
        'retail_unit',
        'unit_id',
        'retail_uom_quintToParent',
        'status',
        'date',
        'image',
        'price',
        'nos_egomania_price',
        'egomania_price',
        'price_retail',
        'nos_gomla_price_retail',
        'gomla_price_retail',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'item_id');
    }

    protected static function booted()
    {
        static::creating(function (Items $item) {
            $item->items_code = self::generateNextCode($item->user_id);
        });
    }

    public static function generateNextCode(int $userId, string $prefix = 'ITM-', int $pad = 6): string
    {
        return DB::transaction(function () use ($userId, $prefix, $pad) {

            $last = Items::where('user_id', $userId)
                ->whereNotNull('items_code')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('items_code');

            $nextNumber = 1;

            if ($last && preg_match('/(\d+)$/', $last, $m)) {
                $nextNumber = ((int) $m[1]) + 1;
            }

            return $prefix.str_pad((string) $nextNumber, $pad, '0', STR_PAD_LEFT);
        });
    }

    public function getCostPriceAttribute(): float
    {
        return (float) ($this->nos_egomania_price ?? 0);
    }

    public function getSellingPriceAttribute(): float
    {
        return (float) ($this->price ?? 0);
    }
}
