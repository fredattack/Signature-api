<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'is_premium',
        'premium_expires_at',
        'mfa_enabled',
        'mfa_secret',
        'locale',
        'timezone',
        'avatar_url',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
        'mfa_secret',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'premium_expires_at' => 'datetime',
            'mfa_enabled' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Signature>
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    /**
     * @return HasOne<Subscription>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * @return HasMany<OauthProvider>
     */
    public function oauthProviders(): HasMany
    {
        return $this->hasMany(OauthProvider::class);
    }

    /**
     * @return HasMany<Session>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}
