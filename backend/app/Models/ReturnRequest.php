<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'order_id',
        'reason',
        'status',
        'timeline',
    ];

    protected $casts = [
        'timeline' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
