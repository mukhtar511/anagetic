<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SizeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shoulder',
        'chest',
        'waist',
        'hip',
        'upper_arm',
        'sleeve_length',
        'full_length',
        'unit',
        'photo',
        'label',
    ];

    protected $casts = [
        'shoulder' => 'decimal:2',
        'chest' => 'decimal:2',
        'waist' => 'decimal:2',
        'hip' => 'decimal:2',
        'upper_arm' => 'decimal:2',
        'sleeve_length' => 'decimal:2',
        'full_length' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
