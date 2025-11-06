<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $celebrity_name
 * @property string|null $color
 * @property string|null $image_data
 * @property string|null $image_url
 * @property string|null $image_path
 * @property int|null $image_width
 * @property int|null $image_height
 * @property int|null $file_size
 * @property string|null $mime_type
 * @property string|null $thumbnail_url
 * @property \Illuminate\Support\Carbon|null $capture_date
 * @property array<string, mixed>|null $device_info
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Signature extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'celebrity_name',
        'color',
        'image_data',
        'image_url',
        'image_path',
        'image_width',
        'image_height',
        'file_size',
        'mime_type',
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
