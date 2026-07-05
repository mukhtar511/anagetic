<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** Only platform admins may enter the Filament panel. SPEC §2 / §7. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }

    protected $fillable = [
        'name',
        'phone',
        'region_id',
        'is_verified_seller',
        'is_admin',
        'fcm_token',
        'phone_verified_at',
        'suspended_at',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'suspended_at' => 'datetime',
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
