<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnalyticsService
{
    /**
     * Track an analytics event
     *
     * @param  array<string, mixed>  $eventData
     */
    public function trackEvent(
        string $eventType,
        ?User $user = null,
        array $eventData = [],
        ?Request $request = null
    ): AnalyticsEvent {
        return AnalyticsEvent::create([
            'id' => Str::uuid(),
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'event_data' => $eventData,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get common event types
     *
     * @return array<string, string>
     */
    public function getEventTypes(): array
    {
        return [
            // Auth events
            'auth.login' => 'User logged in',
            'auth.logout' => 'User logged out',
            'auth.register' => 'User registered',
            'auth.password_reset' => 'Password reset requested',
            'auth.mfa_enabled' => 'MFA enabled',
            'auth.mfa_disabled' => 'MFA disabled',

            // Signature events
            'signature.created' => 'Signature created',
            'signature.updated' => 'Signature updated',
            'signature.deleted' => 'Signature deleted',
            'signature.viewed' => 'Signature viewed',

            // Wallpaper events
            'wallpaper.generated' => 'Wallpaper generated',
            'wallpaper.deleted' => 'Wallpaper deleted',
            'wallpaper.viewed' => 'Wallpaper viewed',
            'wallpaper.downloaded' => 'Wallpaper downloaded',

            // Template events
            'template.viewed' => 'Template viewed',
            'template.list_viewed' => 'Template list viewed',

            // Subscription events
            'subscription.created' => 'Subscription created',
            'subscription.cancelled' => 'Subscription cancelled',
            'subscription.upgraded' => 'Subscription upgraded',
            'subscription.downgraded' => 'Subscription downgraded',

            // Sharing events
            'share.wallpaper' => 'Wallpaper shared',

            // Feedback events
            'feedback.submitted' => 'Feedback submitted',

            // Error events
            'error.premium_required' => 'Premium access required',
            'error.api_error' => 'API error occurred',
        ];
    }

    /**
     * Track user activity
     */
    public function trackActivity(User $user, string $action, mixed $subject = null, ?Request $request = null): void
    {
        $eventData = [
            'action' => $action,
        ];

        if ($subject) {
            $eventData['subject_type'] = get_class($subject);
            $eventData['subject_id'] = $subject->id ?? null;
        }

        $this->trackEvent('user.activity', $user, $eventData, $request);
    }
}
