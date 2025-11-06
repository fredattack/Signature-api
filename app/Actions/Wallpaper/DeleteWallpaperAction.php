<?php

namespace App\Actions\Wallpaper;

use App\Models\Wallpaper;
use Illuminate\Support\Facades\Storage;

class DeleteWallpaperAction
{
    /**
     * Delete a wallpaper and its associated files
     */
    public function execute(string $id, string $userId): bool
    {
        $wallpaper = Wallpaper::whereHas('signature', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->find($id);

        if (! $wallpaper) {
            return false;
        }

        // Extract paths from URLs
        if ($wallpaper->wallpaper_url) {
            $wallpaperPath = $this->extractPathFromUrl($wallpaper->wallpaper_url);
            if ($wallpaperPath) {
                Storage::disk('public')->delete($wallpaperPath);
            }
        }

        if ($wallpaper->thumbnail_url) {
            $thumbnailPath = $this->extractPathFromUrl($wallpaper->thumbnail_url);
            if ($thumbnailPath) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }

        // Delete database record (soft delete)
        return (bool) $wallpaper->delete();
    }

    /**
     * Extract storage path from URL
     */
    private function extractPathFromUrl(string $url): ?string
    {
        // Extract path after /storage/
        if (preg_match('#/storage/(.+)$#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
