<?php

namespace App\Actions\Subscription;

use App\Models\User;

class CheckPremiumAccessAction
{
    /**
     * Check if user has active premium access
     */
    public function execute(User $user): bool
    {
        if (! $user->is_premium) {
            return false;
        }

        if (! $user->premium_expires_at) {
            return false;
        }

        return $user->premium_expires_at > now();
    }

    /**
     * Get premium access details
     *
     * @return array{has_access: bool, expires_at: string|null, days_remaining: int|null}
     */
    public function getAccessDetails(User $user): array
    {
        $hasAccess = $this->execute($user);

        if (! $hasAccess || ! $user->premium_expires_at) {
            return [
                'has_access' => false,
                'expires_at' => null,
                'days_remaining' => null,
            ];
        }

        return [
            'has_access' => true,
            'expires_at' => $user->premium_expires_at->format('c'),
            'days_remaining' => (int) now()->diffInDays($user->premium_expires_at, false),
        ];
    }
}
