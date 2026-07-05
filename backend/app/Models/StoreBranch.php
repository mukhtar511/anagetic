<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'region_id',
        'city',
        'delivery_same_city',
        'delivery_other',
    ];

    protected $casts = [
        'delivery_same_city' => 'decimal:2',
        'delivery_other' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
