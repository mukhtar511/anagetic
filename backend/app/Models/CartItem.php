<?php

namespace App\Models;

use App\Enums\ProductMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'store_id',
        'mode',
        'color',
        'measurements',
        'notes',
        'addons',
        'packaging',
        'packaging_price',
        'qty',
        'unit_price',
        'deposit',
    ];

    protected $casts = [
        'mode' => ProductMode::class,
        'measurements' => 'array',
        'addons' => 'array',
        'packaging_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'deposit' => 'decimal:2',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
