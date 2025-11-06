<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event_type',
        'event_data',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, AnalyticsEvent>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
