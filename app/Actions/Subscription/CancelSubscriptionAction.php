<?php

namespace App\Actions\Subscription;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class CancelSubscriptionAction
{
    /**
     * Cancel user's subscription
     *
     * @param  array{reason?: string, feedback?: string}  $data
     */
    public function execute(User $user, array $data = []): bool
    {
        $subscription = $user->subscription()->first();

        if (! $subscription) {
            return false;
        }

        // Check if already cancelled
        if ($subscription->status === 'cancelled' || $subscription->cancelled_at) {
            return false;
        }

        // Mark subscription as cancelled
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);

        // User retains premium access until end of current period
        // Don't modify is_premium or premium_expires_at here

        // TODO: In production, this would also call Stripe API to cancel subscription
        // Example: Stripe\Subscription::update($subscription->stripe_subscription_id, ['cancel_at_period_end' => true]);

        // Optional: Store cancellation feedback
        if (! empty($data['reason']) || ! empty($data['feedback'])) {
            // Could be stored in a feedback table or sent to analytics
        }

        return true;
    }
}
