<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'refresh_token',
        'ip_address',
        'user_agent',
        'expires_at',
        'last_used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, Session>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
