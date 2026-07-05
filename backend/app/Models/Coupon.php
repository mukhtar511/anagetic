<?php

namespace App\Models;

use App\Enums\CouponKind;
use App\Enums\CouponOwner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner',
        'store_id',
        'code',
        'kind',
        'value',
        'cap',
        'min',
        'used_count',
        'active',
        'note',
    ];

    protected $casts = [
        'owner' => CouponOwner::class,
        'kind' => CouponKind::class,
        'value' => 'decimal:2',
        'cap' => 'decimal:2',
        'min' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }
}
