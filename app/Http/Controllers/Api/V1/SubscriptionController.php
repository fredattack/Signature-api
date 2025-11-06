<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Subscription\CancelSubscriptionAction;
use App\Actions\Subscription\CheckPremiumAccessAction;
use App\Actions\Subscription\GetSubscriptionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\CancelSubscriptionRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Services\SubscriptionPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionController extends Controller
{
    /**
     * Get available subscription plans
     */
    public function plans(SubscriptionPlanService $planService): AnonymousResourceCollection
    {
        $plans = $planService->getPlans();

        // Transform plans array to collection format
        $planCollection = collect($plans)->map(function ($plan, $key) {
            $plan['type'] = $key;

            return $plan;
        })->values();

        return SubscriptionPlanResource::collection($planCollection);
    }

    /**
     * Get current user's subscription
     */
    public function show(GetSubscriptionAction $action): SubscriptionResource|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $subscription = $action->execute($user);

        if (! $subscription) {
            return response()->json([
                'message' => 'No active subscription found.',
                'data' => null,
            ], 404);
        }

        return new SubscriptionResource($subscription);
    }

    /**
     * Get premium access status
     */
    public function status(
        CheckPremiumAccessAction $action,
        SubscriptionPlanService $planService
    ): JsonResponse {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $accessDetails = $action->getAccessDetails($user);
        $planType = $planService->getUserPlanType($user);

        return response()->json([
            'is_premium' => $user->is_premium,
            'plan_type' => $planType,
            'premium_expires_at' => $user->premium_expires_at?->toISOString(),
            'access_details' => $accessDetails,
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel(
        CancelSubscriptionRequest $request,
        CancelSubscriptionAction $action
    ): JsonResponse {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        /** @var array{reason?: string, feedback?: string} $validated */
        $validated = $request->validated();

        $result = $action->execute($user, $validated);

        if (! $result) {
            return response()->json([
                'message' => 'No active subscription to cancel or subscription already cancelled.',
            ], 400);
        }

        return response()->json([
            'message' => 'Subscription cancelled successfully. Your premium access will continue until the end of the current billing period.',
        ], 200);
    }
}
