<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Wallpaper\DeleteWallpaperAction;
use App\Actions\Wallpaper\GenerateWallpaperAction;
use App\Actions\Wallpaper\GetWallpaperAction;
use App\Actions\Wallpaper\ListWallpapersAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallpaper\DeleteWallpaperRequest;
use App\Http\Requests\Wallpaper\GenerateWallpaperRequest;
use App\Http\Requests\Wallpaper\ListWallpapersRequest;
use App\Http\Resources\WallpaperResource;
use App\Models\WallpaperTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WallpaperController extends Controller
{
    /**
     * List all wallpapers for authenticated user
     */
    public function index(
        ListWallpapersRequest $request,
        ListWallpapersAction $action
    ): AnonymousResourceCollection {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        /** @var array{signature_id?: string, template_id?: string, page?: int, per_page?: int} $validated */
        $validated = $request->validated();
        $wallpapers = $action->execute((string) $user->id, $validated);

        return WallpaperResource::collection($wallpapers);
    }

    /**
     * Get a specific wallpaper
     */
    public function show(
        string $id,
        GetWallpaperAction $action
    ): WallpaperResource|JsonResponse {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $wallpaper = $action->execute($id, (string) $user->id);

        if (! $wallpaper) {
            return response()->json([
                'message' => 'Wallpaper not found or does not belong to you.',
            ], 404);
        }

        return new WallpaperResource($wallpaper);
    }

    /**
     * Generate a new wallpaper from signature and template
     */
    public function store(
        GenerateWallpaperRequest $request,
        GenerateWallpaperAction $action
    ): WallpaperResource|JsonResponse {
        try {
            /** @var array{signature_id: string, template_id: string, resolution?: string} $validated */
            $validated = $request->validated();

            // Check if template is premium and user has access
            $template = WallpaperTemplate::findOrFail($validated['template_id']);
            if ($template->is_premium) {
                /** @var \App\Models\User $user */
                $user = auth()->user();

                if (! $user->is_premium || ! $user->premium_expires_at || $user->premium_expires_at->isPast()) {
                    return response()->json([
                        'message' => 'This template requires a premium subscription.',
                        'error' => 'PREMIUM_REQUIRED',
                        'template_id' => $template->id,
                    ], 403);
                }
            }

            $wallpaper = $action->execute($validated);

            return new WallpaperResource($wallpaper->load(['signature', 'template']));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate wallpaper.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a wallpaper (soft delete)
     */
    public function destroy(
        string $id,
        DeleteWallpaperRequest $request,
        DeleteWallpaperAction $action
    ): JsonResponse {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $result = $action->execute($id, (string) $user->id);

        if (! $result) {
            return response()->json([
                'message' => 'Wallpaper not found or could not be deleted.',
            ], 404);
        }

        return response()->json([
            'message' => 'Wallpaper deleted successfully.',
        ], 200);
    }
}
