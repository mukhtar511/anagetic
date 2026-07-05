<?php

namespace App\Models;

use App\Enums\CommMode;
use App\Enums\StoreStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'tagline',
        'bio',
        'region_id',
        'status',
        'comm_mode',
        'phone_visible',
        'phone',
        'packaging',
        'delivery_free_km',
        'delivery_flat_price',
        'verification_status',
        'verification_docs',
        'joined_year',
        'rating_avg',
        'completed_orders',
        'on_time_rate',
    ];

    protected $casts = [
        'status' => StoreStatus::class,
        'comm_mode' => CommMode::class,
        'phone_visible' => 'boolean',
        'packaging' => 'array',
        'delivery_flat_price' => 'decimal:2',
        'verification_docs' => 'array',
        'rating_avg' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(StoreBranch::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function smartRequestOffers(): HasMany
    {
        return $this->hasMany(SmartRequestOffer::class);
    }
}
