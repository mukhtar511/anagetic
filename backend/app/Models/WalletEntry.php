<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletEntry extends Model
{
    use HasFactory;

    protected $table = 'wallet_ledger';

    protected $fillable = [
        'wallet_id',
        'type',
        'ref',
        'text',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
