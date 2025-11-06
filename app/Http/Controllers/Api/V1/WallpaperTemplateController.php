<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\WallpaperTemplate\GetWallpaperTemplateAction;
use App\Actions\WallpaperTemplate\ListWallpaperTemplatesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\WallpaperTemplate\ListWallpaperTemplatesRequest;
use App\Http\Resources\WallpaperTemplateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WallpaperTemplateController extends Controller
{
    /**
     * List all active wallpaper templates
     */
    public function index(
        ListWallpaperTemplatesRequest $request,
        ListWallpaperTemplatesAction $action
    ): AnonymousResourceCollection {
        /** @var array{category?: string, is_premium?: bool, page?: int, per_page?: int} $validated */
        $validated = $request->validated();
        $templates = $action->execute($validated);

        return WallpaperTemplateResource::collection($templates);
    }

    /**
     * Get a specific wallpaper template
     */
    public function show(
        string $id,
        GetWallpaperTemplateAction $action
    ): WallpaperTemplateResource|JsonResponse {
        $template = $action->execute($id);

        if (! $template) {
            return response()->json([
                'message' => 'Template not found or inactive.',
            ], 404);
        }

        return new WallpaperTemplateResource($template);
    }
}
