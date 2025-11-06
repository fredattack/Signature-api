<?php

namespace App\Actions\Subscription;

use App\Models\Subscription;
use App\Models\User;

class GetSubscriptionAction
{
    /**
     * Get user's active subscription
     */
    public function execute(User $user): ?Subscription
    {
        return $user->subscription()->first();
    }
}
