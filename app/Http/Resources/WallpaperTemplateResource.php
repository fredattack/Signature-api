<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WallpaperTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\WallpaperTemplate $template */
        $template = $this->resource;

        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'preview_url' => $template->preview_url,
            'category' => $template->category,
            'is_premium' => $template->is_premium,
            'config' => $template->config,
            'created_at' => $template->created_at?->toISOString(),
            'updated_at' => $template->updated_at?->toISOString(),
        ];
    }
}
