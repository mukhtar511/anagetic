<?php

namespace App\Models;

use App\Enums\SmartRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmartRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'type',
        'description',
        'category',
        'size',
        'budget',
        'need_by',
        'scope',
        'region_id',
        'ref_images',
        'change_notes',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'need_by' => 'date',
        'ref_images' => 'array',
        'status' => SmartRequestStatus::class,
        'expires_at' => 'datetime',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(SmartRequestOffer::class);
    }
}
