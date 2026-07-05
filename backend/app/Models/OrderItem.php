<?php

namespace App\Models;

use App\Enums\ProductMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'store_id',
        'title',
        'mode',
        'color',
        'size_profile_id',
        'measurements',
        'notes',
        'addons',
        'packaging',
        'packaging_price',
        'qty',
        'unit_price',
        'addons_total',
        'deposit',
        'line_total',
        'delivery_code',
    ];

    protected $casts = [
        'mode' => ProductMode::class,
        'measurements' => 'array',
        'addons' => 'array',
        'packaging_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'addons_total' => 'decimal:2',
        'deposit' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sizeProfile(): BelongsTo
    {
        return $this->belongsTo(SizeProfile::class);
    }
}
