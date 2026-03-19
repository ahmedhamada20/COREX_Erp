<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_type_id',
        'parent_account_id',
        'name',
        'account_number',
        'start_balance',
        'current_balance',
        'other_table_id',
        'notes',
        'status',
        'date',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'date' => 'date',
        'start_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $account) {
            if (is_null($account->current_balance)) {
                $account->current_balance = $account->start_balance ?? 0;
            }
        });

        static::creating(function (self $model) {
            // ✅ لا تلمس code إلا لو العمود موجود فعلاً
            if (! Schema::hasColumn('accounts', 'code')) {
                return;
            }

            if (empty($model->code) && ! empty($model->account_number)) {
                $model->code = $model->account_number;
            }
            if (empty($model->account_number) && ! empty($model->code)) {
                $model->account_number = $model->code;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function type()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_account_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_account_id')->orderBy('id');
    }

    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    public function scopeMine(Builder $q): Builder
    {
        return $q->where('user_id', auth()->id() ?? 0);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', true);
    }

    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_account_id');
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_account_id);
    }

    public function getPathAttribute(): string
    {
        $names = [];
        $node = $this;

        while ($node) {
            $names[] = $node->name;
            $node = $node->parent;
        }

        return implode(' > ', array_reverse($names));
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    /**
     * Get journal entry lines for this account
     */
    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
