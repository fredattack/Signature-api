<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WallpaperResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Wallpaper $wallpaper */
        $wallpaper = $this->resource;

        return [
            'id' => $wallpaper->id,
            'signature_id' => $wallpaper->signature_id,
            'template_id' => $wallpaper->template_id,
            'wallpaper_url' => $wallpaper->wallpaper_url,
            'thumbnail_url' => $wallpaper->thumbnail_url,
            'resolution' => $wallpaper->resolution,
            'file_size' => $wallpaper->file_size,
            'generation_status' => $wallpaper->generation_status,
            'signature' => new SignatureResource($this->whenLoaded('signature')),
            'template' => new WallpaperTemplateResource($this->whenLoaded('template')),
            'created_at' => $wallpaper->created_at?->toISOString(),
            'updated_at' => $wallpaper->updated_at?->toISOString(),
        ];
    }
}
