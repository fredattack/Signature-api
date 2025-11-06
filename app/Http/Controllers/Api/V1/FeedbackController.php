<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Feedback\GetFeedbackStatsAction;
use App\Actions\Feedback\ListFeedbackAction;
use App\Actions\Feedback\SubmitFeedbackAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\ListFeedbackRequest;
use App\Http\Requests\Feedback\SubmitFeedbackRequest;
use App\Http\Resources\FeedbackResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedbackController extends Controller
{
    /**
     * Submit feedback
     */
    public function store(
        SubmitFeedbackRequest $request,
        SubmitFeedbackAction $action
    ): FeedbackResource {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        /** @var array{type: string, subject: string, message: string} $validated */
        $validated = $request->validated();

        $feedback = $action->execute($user, $validated, $request);

        return new FeedbackResource($feedback);
    }

    /**
     * List user's feedback
     */
    public function index(
        ListFeedbackRequest $request,
        ListFeedbackAction $action
    ): AnonymousResourceCollection {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        /** @var array{type?: string, status?: string, page?: int, per_page?: int} $validated */
        $validated = $request->validated();

        $feedback = $action->execute($user, $validated);

        return FeedbackResource::collection($feedback);
    }

    /**
     * Get feedback statistics
     */
    public function stats(GetFeedbackStatsAction $action): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $stats = $action->execute($user);

        return response()->json($stats);
    }
}
