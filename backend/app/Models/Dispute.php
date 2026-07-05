<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'order_id',
        'deposit_id',
        'evidence',
        'decision',
        'cut_value',
        'decided_by',
        'status',
    ];

    protected $casts = [
        'evidence' => 'array',
        'cut_value' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
