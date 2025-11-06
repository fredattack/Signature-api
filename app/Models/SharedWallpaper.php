<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedWallpaper extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'wallpaper_id',
        'user_id',
        'platform',
        'shared_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shared_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Wallpaper, SharedWallpaper>
     */
    public function wallpaper(): BelongsTo
    {
        return $this->belongsTo(Wallpaper::class);
    }

    /**
     * @return BelongsTo<User, SharedWallpaper>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
