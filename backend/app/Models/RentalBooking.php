<?php

namespace App\Models;

use App\Enums\RentalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'order_id',
        'occasion_date',
        'block_from',
        'block_to',
        'inspection_day',
        'status',
    ];

    protected $casts = [
        'occasion_date' => 'date',
        'block_from' => 'date',
        'block_to' => 'date',
        'inspection_day' => 'date',
        'status' => RentalStatus::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
