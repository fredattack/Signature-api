<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SignatureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Signature $signature */
        $signature = $this->resource;

        return [
            'id' => $signature->id,
            'user_id' => $signature->user_id,
            'name' => $signature->name,
            'description' => $signature->description,
            'image_url' => $signature->image_path ? Storage::disk('public')->url($signature->image_path) : null,
            'image_path' => $signature->image_path,
            'image_width' => $signature->image_width,
            'image_height' => $signature->image_height,
            'file_size' => $signature->file_size,
            'mime_type' => $signature->mime_type,
            'created_at' => $signature->created_at?->toISOString(),
            'updated_at' => $signature->updated_at?->toISOString(),
        ];
    }
}
