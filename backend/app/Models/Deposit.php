<?php

namespace App\Models;

use App\Enums\DepositStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'buyer_id',
        'store_id',
        'amount',
        'status',
        'cut_amount',
        'cut_reason',
        'deposit_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => DepositStatus::class,
        'cut_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(AppealTicket::class);
    }
}
