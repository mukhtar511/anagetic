<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationCenter extends Model
{
    use HasFactory;

    protected $table = 'notifications_center';

    protected $fillable = [
        'user_id',
        'ic',
        'title',
        'body',
        'go',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
