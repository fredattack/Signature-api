<?php

namespace Tests\Feature\Feedback;

use App\Models\Feedback;
use App\Models\User;
use App\Services\JwtService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_feedback(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        // Create feedback for this user
        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Bug report',
            'message' => 'Found a bug',
            'status' => 'pending',
            'created_at' => Carbon::now(),
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'feature',
            'subject' => 'Feature request',
            'message' => 'New feature idea',
            'status' => 'pending',
            'created_at' => Carbon::now()->subDay(),
        ]);

        // Create feedback for another user (should not be visible)
        $otherUser = User::create([
            'id' => Str::uuid(),
            'email' => 'other@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $otherUser->id,
            'type' => 'bug',
            'subject' => 'Other user feedback',
            'message' => 'Other user message',
            'status' => 'pending',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/feedback');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'type',
                        'subject',
                        'message',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        // Verify we only see this user's feedback
        $data = $response->json('data');
        foreach ($data as $feedback) {
            $this->assertEquals($user->id, $feedback['user_id']);
        }
    }

    public function test_user_can_filter_feedback_by_type(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Bug report',
            'message' => 'Found a bug',
            'status' => 'pending',
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'feature',
            'subject' => 'Feature request',
            'message' => 'New feature',
            'status' => 'pending',
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'general',
            'subject' => 'General feedback',
            'message' => 'General comment',
            'status' => 'pending',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/feedback?type=bug');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $data = $response->json('data');
        $this->assertEquals('bug', $data[0]['type']);
    }

    public function test_user_can_filter_feedback_by_status(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Pending bug',
            'message' => 'Pending bug report',
            'status' => 'pending',
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Resolved bug',
            'message' => 'Resolved bug report',
            'status' => 'resolved',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/feedback?status=resolved');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $data = $response->json('data');
        $this->assertEquals('resolved', $data[0]['status']);
    }

    public function test_user_can_filter_by_both_type_and_status(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Pending bug',
            'message' => 'Pending bug report',
            'status' => 'pending',
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Resolved bug',
            'message' => 'Resolved bug report',
            'status' => 'resolved',
        ]);

        Feedback::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => 'feature',
            'subject' => 'Resolved feature',
            'message' => 'Resolved feature',
            'status' => 'resolved',
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/feedback?type=bug&status=resolved');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $data = $response->json('data');
        $this->assertEquals('bug', $data[0]['type']);
        $this->assertEquals('resolved', $data[0]['status']);
    }

    public function test_feedback_list_supports_pagination(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        // Create 15 feedback items
        for ($i = 0; $i < 15; $i++) {
            Feedback::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'type' => 'bug',
                'subject' => "Bug report {$i}",
                'message' => "Bug message {$i}",
                'status' => 'pending',
            ]);
        }

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        // Test first page
        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/feedback?page=1&per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        // Test second page
        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/feedback?page=2&per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_feedback_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/feedback');

        $response->assertStatus(401);
    }

    public function test_feedback_list_validates_type_filter(): void
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
            ->getJson('/api/v1/feedback?type=invalid_type');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_feedback_list_validates_status_filter(): void
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
            ->getJson('/api/v1/feedback?status=invalid_status');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
