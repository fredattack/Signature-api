<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Feedback $feedback */
        $feedback = $this->resource;

        return [
            'id' => $feedback->id,
            'user_id' => $feedback->user_id,
            'type' => $feedback->type,
            'subject' => $feedback->subject,
            'message' => $feedback->message,
            'status' => $feedback->status,
            'created_at' => $feedback->created_at?->format('c'),
            'updated_at' => $feedback->updated_at?->format('c'),
        ];
    }
}
