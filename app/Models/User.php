<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'preferred_locale', 'in_app_notifications', 'email_notifications'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'in_app_notifications' => 'boolean',
            'email_notifications' => 'boolean',
        ];
    }

    public function moderatorGrants(): HasMany
    {
        return $this->hasMany(ModeratorGrant::class);
    }

    public function moderatorMfa(): HasOne
    {
        return $this->hasOne(ModeratorMfa::class);
    }

    public function followedConfessions(): BelongsToMany
    {
        return $this->belongsToMany(Confession::class, 'confession_follows')->withPivot('created_at');
    }

    public function ownedConfessions(): BelongsToMany
    {
        return $this->belongsToMany(Confession::class, 'confession_ownerships', 'user_id', 'confession_id');
    }

    public function accountNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }
}
