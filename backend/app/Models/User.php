<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'region_id',
        'is_verified_seller',
        'is_admin',
        'fcm_token',
        'phone_verified_at',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'is_verified_seller' => 'boolean',
        'is_admin' => 'boolean',
        'password' => 'hashed',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function sizeProfiles(): HasMany
    {
        return $this->hasMany(SizeProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function notificationsCenter(): HasMany
    {
        return $this->hasMany(NotificationCenter::class);
    }

    public function smartRequests(): HasMany
    {
        return $this->hasMany(SmartRequest::class, 'buyer_id');
    }
}
