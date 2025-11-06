<?php

namespace App\Actions\Analytics;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class GetUserStatsAction
{
    /**
     * Get comprehensive user statistics
     *
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        return [
            'signatures' => $this->getSignatureStats($user),
            'wallpapers' => $this->getWallpaperStats($user),
            'subscription' => $this->getSubscriptionStats($user),
            'activity' => $this->getActivityStats($user),
        ];
    }

    /**
     * Get signature statistics
     *
     * @return array<string, mixed>
     */
    private function getSignatureStats(User $user): array
    {
        $signatures = $user->signatures();

        return [
            'total' => $signatures->count(),
            'created_this_month' => $signatures->where('created_at', '>=', now()->startOfMonth())->count(),
            'most_recent' => $signatures->latest()->first()?->created_at?->format('c'),
        ];
    }

    /**
     * Get wallpaper statistics
     *
     * @return array<string, mixed>
     */
    private function getWallpaperStats(User $user): array
    {
        $wallpapers = DB::table('wallpapers')
            ->join('signatures', 'wallpapers.signature_id', '=', 'signatures.id')
            ->where('signatures.user_id', $user->id)
            ->whereNull('wallpapers.deleted_at');

        return [
            'total' => $wallpapers->count(),
            'generated_this_month' => $wallpapers->where('wallpapers.created_at', '>=', now()->startOfMonth())->count(),
            'most_used_template' => $this->getMostUsedTemplate($user),
        ];
    }

    /**
     * Get subscription statistics
     *
     * @return array<string, mixed>
     */
    private function getSubscriptionStats(User $user): array
    {
        $subscription = $user->subscription;

        return [
            'is_premium' => $user->is_premium,
            'plan_type' => $subscription ? $subscription->plan_type : 'free',
            'status' => $subscription ? $subscription->status : null,
            'expires_at' => $user->premium_expires_at ? $user->premium_expires_at->format('c') : null,
            'days_remaining' => $user->premium_expires_at ? (int) now()->diffInDays($user->premium_expires_at, false) : null,
        ];
    }

    /**
     * Get activity statistics
     *
     * @return array<string, mixed>
     */
    private function getActivityStats(User $user): array
    {
        $events = DB::table('analytics_events')
            ->where('user_id', $user->id);

        return [
            'total_events' => $events->count(),
            'events_this_week' => $events->where('created_at', '>=', now()->startOfWeek())->count(),
            'most_common_actions' => $this->getMostCommonActions($user),
            'last_active' => $events->max('created_at'),
        ];
    }

    /**
     * Get most used template
     */
    private function getMostUsedTemplate(User $user): ?string
    {
        /** @var object{name: string, usage_count: int}|null $result */
        $result = DB::table('wallpapers')
            ->join('signatures', 'wallpapers.signature_id', '=', 'signatures.id')
            ->join('wallpaper_templates', 'wallpapers.template_id', '=', 'wallpaper_templates.id')
            ->where('signatures.user_id', $user->id)
            ->whereNull('wallpapers.deleted_at')
            ->select('wallpaper_templates.name', DB::raw('count(*) as usage_count'))
            ->groupBy('wallpaper_templates.id', 'wallpaper_templates.name')
            ->orderByDesc('usage_count')
            ->first();

        return $result ? $result->name : null;
    }

    /**
     * Get most common actions
     *
     * @return array<array<string, mixed>>
     */
    private function getMostCommonActions(User $user): array
    {
        $results = DB::table('analytics_events')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return $results->map(fn ($row) => [
            'event_type' => $row->event_type,
            'count' => $row->count,
        ])->toArray();
    }
}
