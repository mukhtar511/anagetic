<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'category',
        'title',
        'description',
        'condition',
        'code',
        'collection',
        'region_id',
        'status',
        'is_single_piece',
        'images',
        'packaging_override',
        'comm_mode_override',
        'original_price',
        'return_policy_ack',
    ];

    protected $casts = [
        'is_single_piece' => 'boolean',
        'images' => 'array',
        'packaging_override' => 'array',
        'original_price' => 'decimal:2',
        'return_policy_ack' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function modes(): HasMany
    {
        return $this->hasMany(ProductSaleMode::class);
    }

    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ProductAddon::class);
    }

    public function featured(): HasMany
    {
        return $this->hasMany(ListingFeatured::class);
    }

    public function rentalBookings(): HasMany
    {
        return $this->hasMany(RentalBooking::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}
