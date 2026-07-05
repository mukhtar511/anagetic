<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'buyer_id',
        'store_id',
        'parent_order_id',
        'status',
        'subtotal',
        'delivery_fee',
        'discount',
        'deposit_total',
        'total',
        'is_custom',
        'region_id',
        'smart_request_id',
        'payment_method',
        'cancellable',
        'placed_at',
        'accepted_at',
        'delivered_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'deposit_total' => 'decimal:2',
        'total' => 'decimal:2',
        'is_custom' => 'boolean',
        'cancellable' => 'boolean',
        'placed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function smartRequest(): BelongsTo
    {
        return $this->belongsTo(SmartRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_order_id');
    }

    public function escrowTransactions(): HasMany
    {
        return $this->hasMany(EscrowTransaction::class);
    }

    public function rentalBookings(): HasMany
    {
        return $this->hasMany(RentalBooking::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }
}
