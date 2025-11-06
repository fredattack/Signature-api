<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Analytics\GetUserEventsAction;
use App\Actions\Analytics\GetUserStatsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnalyticsEventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AnalyticsController extends Controller
{
    /**
     * Get user statistics
     */
    public function stats(GetUserStatsAction $action): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $stats = $action->execute($user);

        return response()->json($stats);
    }

    /**
     * Get user events history
     */
    public function events(Request $request, GetUserEventsAction $action): AnonymousResourceCollection
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'event_type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var array{event_type?: string, start_date?: string, end_date?: string, page?: int, per_page?: int} $filters */
        $filters = $validated;

        $events = $action->execute($user, $filters);

        return AnalyticsEventResource::collection($events);
    }
}
