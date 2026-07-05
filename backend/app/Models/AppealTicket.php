<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppealTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ref',
        'deposit_id',
        'user_id',
        'status',
        'reviewed_by',
        'resolution',
    ];

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
