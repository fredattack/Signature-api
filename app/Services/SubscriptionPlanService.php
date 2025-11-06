<?php

namespace App\Services;

class SubscriptionPlanService
{
    /**
     * Available subscription plans
     *
     * @return array<string, array<string, mixed>>
     */
    public function getPlans(): array
    {
        return [
            'free' => [
                'name' => 'Free',
                'price' => 0,
                'currency' => 'USD',
                'interval' => null,
                'features' => [
                    'signatures' => 3,
                    'wallpapers_per_month' => 10,
                    'templates' => 'basic',
                    'storage_mb' => 50,
                    'export_formats' => ['png'],
                ],
                'description' => 'Perfect for trying out the app',
            ],
            'monthly' => [
                'name' => 'Premium Monthly',
                'price' => 4.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => [
                    'signatures' => 'unlimited',
                    'wallpapers_per_month' => 'unlimited',
                    'templates' => 'all',
                    'storage_mb' => 500,
                    'export_formats' => ['png', 'jpg', 'webp'],
                    'priority_support' => true,
                    'no_watermark' => true,
                ],
                'description' => 'All features, billed monthly',
            ],
            'annual' => [
                'name' => 'Premium Annual',
                'price' => 49.99,
                'currency' => 'USD',
                'interval' => 'year',
                'save_percentage' => 17,
                'features' => [
                    'signatures' => 'unlimited',
                    'wallpapers_per_month' => 'unlimited',
                    'templates' => 'all',
                    'storage_mb' => 1000,
                    'export_formats' => ['png', 'jpg', 'webp', 'svg'],
                    'priority_support' => true,
                    'no_watermark' => true,
                    'early_access' => true,
                ],
                'description' => 'Best value - save 17% with annual billing',
            ],
        ];
    }

    /**
     * Get a specific plan
     *
     * @return array<string, mixed>|null
     */
    public function getPlan(string $planType): ?array
    {
        $plans = $this->getPlans();

        return $plans[$planType] ?? null;
    }

    /**
     * Check if a plan type is valid
     */
    public function isValidPlanType(string $planType): bool
    {
        return in_array($planType, ['free', 'monthly', 'annual']);
    }

    /**
     * Check if a plan is premium
     */
    public function isPremiumPlan(string $planType): bool
    {
        return in_array($planType, ['monthly', 'annual']);
    }

    /**
     * Get user's effective plan type
     */
    public function getUserPlanType(\App\Models\User $user): string
    {
        if ($user->is_premium && $user->premium_expires_at instanceof \DateTimeInterface && $user->premium_expires_at > now()) {
            $subscription = $user->subscription;
            if ($subscription && $subscription->status === 'active') {
                return $subscription->plan_type;
            }

            return 'monthly'; // Default for legacy premium users
        }

        return 'free';
    }
}
