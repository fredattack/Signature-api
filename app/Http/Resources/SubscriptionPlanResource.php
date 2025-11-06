<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $plan */
        $plan = $this->resource;

        return [
            'name' => $plan['name'],
            'price' => $plan['price'],
            'currency' => $plan['currency'],
            'interval' => $plan['interval'],
            'save_percentage' => $plan['save_percentage'] ?? null,
            'features' => $plan['features'],
            'description' => $plan['description'],
        ];
    }
}
