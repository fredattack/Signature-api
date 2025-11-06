<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'is_premium' => $user->is_premium,
            'premium_expires_at' => $user->premium_expires_at?->toIso8601String(),
            'mfa_enabled' => $user->mfa_enabled,
            'locale' => $user->locale,
            'timezone' => $user->timezone,
            'avatar_url' => $user->avatar_url,
            'created_at' => $user->created_at?->toIso8601String() ?? null,
        ];
    }
}
