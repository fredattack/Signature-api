<?php

namespace App\Actions\Wallpaper;

use App\Models\Wallpaper;
use Illuminate\Pagination\LengthAwarePaginator;

class ListWallpapersAction
{
    /**
     * List wallpapers for the authenticated user with optional filtering
     *
     * @param  array{signature_id?: string, template_id?: string, page?: int, per_page?: int}  $filters
     */
    public function execute(string $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Wallpaper::query()
            ->whereHas('signature', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['signature', 'template'])
            ->orderBy('created_at', 'desc');

        // Filter by signature
        if (! empty($filters['signature_id'])) {
            $query->where('signature_id', $filters['signature_id']);
        }

        // Filter by template
        if (! empty($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }

        // Paginate
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }
}
