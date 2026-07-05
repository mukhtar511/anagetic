<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartRequestOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'smart_request_id',
        'store_id',
        'price',
        'message',
        'delivery_note',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function smartRequest(): BelongsTo
    {
        return $this->belongsTo(SmartRequest::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
