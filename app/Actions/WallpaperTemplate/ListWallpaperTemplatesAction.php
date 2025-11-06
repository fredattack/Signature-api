<?php

namespace App\Actions\WallpaperTemplate;

use App\Models\WallpaperTemplate;
use Illuminate\Pagination\LengthAwarePaginator;

class ListWallpaperTemplatesAction
{
    /**
     * List wallpaper templates with optional filtering
     *
     * @param  array{category?: string, is_premium?: bool, page?: int, per_page?: int}  $filters
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = WallpaperTemplate::query()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

        // Filter by category
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Filter by premium status
        if (isset($filters['is_premium'])) {
            $query->where('is_premium', $filters['is_premium']);
        }

        // Paginate
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }
}
