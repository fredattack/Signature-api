<?php

namespace App\Actions\WallpaperTemplate;

use App\Models\WallpaperTemplate;

class GetWallpaperTemplateAction
{
    /**
     * Get a single wallpaper template by ID
     */
    public function execute(string $id): ?WallpaperTemplate
    {
        return WallpaperTemplate::where('is_active', true)->find($id);
    }
}
