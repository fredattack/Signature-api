<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\AnalyticsEvent $event */
        $event = $this->resource;

        return [
            'id' => $event->id,
            'user_id' => $event->user_id,
            'event_type' => $event->event_type,
            'event_data' => $event->event_data,
            'ip_address' => $event->ip_address,
            'created_at' => $event->created_at->format('c'),
        ];
    }
}
