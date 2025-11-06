<?php

namespace App\Actions\Feedback;

use App\Models\Feedback;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubmitFeedbackAction
{
    public function __construct(
        protected AnalyticsService $analytics
    ) {}

    /**
     * Submit user feedback
     *
     * @param  array{type: string, subject: string, message: string}  $data
     */
    public function execute(User $user, array $data, ?Request $request = null): Feedback
    {
        $feedback = Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => $data['type'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'pending',
        ]);

        // Track analytics event
        $this->analytics->trackEvent(
            'feedback.submitted',
            $user,
            [
                'feedback_id' => $feedback->id,
                'type' => $data['type'],
            ],
            $request
        );

        return $feedback;
    }
}
