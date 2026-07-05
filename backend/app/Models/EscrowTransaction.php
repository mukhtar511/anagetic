<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscrowTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'store_id',
        'held_amount',
        'coupon_discount',
        'commission',
        'net_amount',
        'status',
        'released_at',
    ];

    protected $casts = [
        'held_amount' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'commission' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'released_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
