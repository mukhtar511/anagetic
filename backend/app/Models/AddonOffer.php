<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddonOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'order_id',
        'name',
        'price',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
