<?php

namespace App\Actions\Feedback;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class GetFeedbackStatsAction
{
    /**
     * Get feedback statistics for user
     *
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $feedbackQuery = DB::table('feedback')->where('user_id', $user->id);

        $byType = DB::table('feedback')
            ->where('user_id', $user->id)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $byStatus = DB::table('feedback')
            ->where('user_id', $user->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $feedbackQuery->count(),
            'by_type' => [
                'bug' => $byType['bug'] ?? 0,
                'feature' => $byType['feature'] ?? 0,
                'general' => $byType['general'] ?? 0,
            ],
            'by_status' => [
                'pending' => $byStatus['pending'] ?? 0,
                'reviewed' => $byStatus['reviewed'] ?? 0,
                'resolved' => $byStatus['resolved'] ?? 0,
            ],
            'most_recent' => $feedbackQuery->max('created_at'),
        ];
    }
}
