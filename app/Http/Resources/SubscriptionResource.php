<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Subscription $subscription */
        $subscription = $this->resource;

        return [
            'id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'status' => $subscription->status,
            'plan_type' => $subscription->plan_type,
            'current_period_start' => $subscription->current_period_start->format('c'),
            'current_period_end' => $subscription->current_period_end->format('c'),
            'cancelled_at' => $subscription->cancelled_at?->format('c'),
            'is_active' => $subscription->status === 'active' && ! $subscription->cancelled_at,
            'created_at' => $subscription->created_at?->format('c'),
            'updated_at' => $subscription->updated_at?->format('c'),
        ];
    }
}
