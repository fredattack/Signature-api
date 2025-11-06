<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthProvider extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
    ];

    /**
     * @return BelongsTo<User, OauthProvider>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
