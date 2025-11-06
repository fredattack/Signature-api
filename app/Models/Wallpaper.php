<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallpaper extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'signature_id',
        'template_id',
        'wallpaper_url',
        'thumbnail_url',
        'resolution',
        'file_size',
        'generation_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Signature, Wallpaper>
     */
    public function signature(): BelongsTo
    {
        return $this->belongsTo(Signature::class);
    }

    /**
     * @return BelongsTo<WallpaperTemplate, Wallpaper>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WallpaperTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<SharedWallpaper>
     */
    public function shares(): HasMany
    {
        return $this->hasMany(SharedWallpaper::class);
    }
}
