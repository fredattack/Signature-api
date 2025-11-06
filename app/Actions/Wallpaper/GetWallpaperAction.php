<?php

namespace App\Actions\Wallpaper;

use App\Models\Wallpaper;

class GetWallpaperAction
{
    /**
     * Get a single wallpaper by ID (ensuring user owns it via signature)
     */
    public function execute(string $id, string $userId): ?Wallpaper
    {
        return Wallpaper::with(['signature', 'template'])
            ->whereHas('signature', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->find($id);
    }
}
