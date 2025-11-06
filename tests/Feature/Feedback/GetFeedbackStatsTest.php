<?php

namespace Tests\Feature\Feedback;

use App\Models\Feedback;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetFeedbackStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_feedback_stats(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        // Create various feedback items
        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Bug 1',
            'message' => 'Bug message 1',
            'status' => 'pending',
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Bug 2',
            'message' => 'Bug message 2',
            'status' => 'resolved',
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'feature',
            'subject' => 'Feature 1',
            'message' => 'Feature message 1',
            'status' => 'pending',
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'general',
            'subject' => 'General 1',
            'message' => 'General message 1',
            'status' => 'resolved',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/feedback/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'by_type' => [
                    'bug',
                    'feature',
                    'general',
                ],
                'by_status' => [
                    'pending',
                    'reviewed',
                    'resolved',
                ],
                'most_recent',
            ])
            ->assertJson([
                'total' => 4,
                'by_type' => [
                    'bug' => 2,
                    'feature' => 1,
                    'general' => 1,
                ],
                'by_status' => [
                    'pending' => 2,
                    'reviewed' => 0,
                    'resolved' => 2,
                ],
            ]);
    }

    public function test_feedback_stats_only_show_user_data(): void
    {
        $user1 = User::create([
            'id' => Str::uuid(),
            'email' => 'user1@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        $user2 = User::create([
            'id' => Str::uuid(),
            'email' => 'user2@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        // Create feedback for user1
        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user1->id,
            'type' => 'bug',
            'subject' => 'User 1 bug',
            'message' => 'User 1 bug message',
            'status' => 'pending',
        ]);

        // Create feedback for user2
        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user2->id,
            'type' => 'bug',
            'subject' => 'User 2 bug',
            'message' => 'User 2 bug message',
            'status' => 'pending',
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user2->id,
            'type' => 'feature',
            'subject' => 'User 2 feature',
            'message' => 'User 2 feature message',
            'status' => 'resolved',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user1);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/feedback/stats');

        $response->assertStatus(200)
            ->assertJson([
                'total' => 1,
                'by_type' => [
                    'bug' => 1,
                    'feature' => 0,
                    'general' => 0,
                ],
            ]);
    }

    public function test_feedback_stats_with_no_feedback(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/feedback/stats');

        $response->assertStatus(200)
            ->assertJson([
                'total' => 0,
                'by_type' => [
                    'bug' => 0,
                    'feature' => 0,
                    'general' => 0,
                ],
                'by_status' => [
                    'pending' => 0,
                    'reviewed' => 0,
                    'resolved' => 0,
                ],
            ]);
    }

    public function test_feedback_stats_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/feedback/stats');

        $response->assertStatus(401);
    }
}
