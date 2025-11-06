<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Signature extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'celebrity_name',
        'color',
        'image_data',
        'image_url',
        'thumbnail_url',
        'capture_date',
        'device_info',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capture_date' => 'datetime',
            'device_info' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, Signature>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Wallpaper>
     */
    public function wallpapers(): HasMany
    {
        return $this->hasMany(Wallpaper::class);
    }
}
