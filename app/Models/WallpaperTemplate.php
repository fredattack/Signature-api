<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WallpaperTemplate extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'preview_url',
        'category',
        'is_premium',
        'is_active',
        'config',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    /**
     * @return HasMany<Wallpaper>
     */
    public function wallpapers(): HasMany
    {
        return $this->hasMany(Wallpaper::class, 'template_id');
    }
}
