<?php

namespace App\Models;

use App\Enums\FeaturedScope;
use App\Enums\FeaturedStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingFeatured extends Model
{
    use HasFactory;

    protected $table = 'listings_featured';

    protected $fillable = [
        'product_id',
        'scope',
        'days',
        'price',
        'starts_at',
        'expires_at',
        'status',
        'expiring_notified',
    ];

    protected $casts = [
        'scope' => FeaturedScope::class,
        'price' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'status' => FeaturedStatus::class,
        'expiring_notified' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
