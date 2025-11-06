<?php

namespace Tests\Feature\Feedback;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubmitFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_feedback(): void
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
            ->postJson('/api/v1/feedback', [
                'type' => 'bug',
                'subject' => 'Test feedback subject',
                'message' => 'This is a test feedback message describing an issue.',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'type',
                    'subject',
                    'message',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'user_id' => $user->id,
                    'type' => 'bug',
                    'subject' => 'Test feedback subject',
                    'message' => 'This is a test feedback message describing an issue.',
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('feedback', [
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Test feedback subject',
            'status' => 'pending',
        ]);
    }

    public function test_can_submit_feature_request_feedback(): void
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
            ->postJson('/api/v1/feedback', [
                'type' => 'feature',
                'subject' => 'New feature request',
                'message' => 'I would like to request a new feature for the app.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'type' => 'feature',
                    'status' => 'pending',
                ],
            ]);
    }

    public function test_can_submit_general_feedback(): void
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
            ->postJson('/api/v1/feedback', [
                'type' => 'general',
                'subject' => 'General feedback',
                'message' => 'Just wanted to say the app is great!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'type' => 'general',
                    'status' => 'pending',
                ],
            ]);
    }

    public function test_feedback_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/feedback', [
            'type' => 'bug',
            'subject' => 'Test feedback',
            'message' => 'This should fail without authentication.',
        ]);

        $response->assertStatus(401);
    }

    public function test_feedback_requires_valid_type(): void
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
            ->postJson('/api/v1/feedback', [
                'type' => 'invalid_type',
                'subject' => 'Test feedback',
                'message' => 'This should fail with invalid type.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_feedback_requires_subject(): void
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
            ->postJson('/api/v1/feedback', [
                'type' => 'bug',
                'message' => 'This should fail without subject.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }

    public function test_feedback_requires_message(): void
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
            ->postJson('/api/v1/feedback', [
                'type' => 'bug',
                'subject' => 'Test feedback',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_feedback_subject_has_max_length(): void
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
            ->postJson('/api/v1/feedback', [
                'type' => 'bug',
                'subject' => str_repeat('a', 201), // 201 characters, exceeds max of 200
                'message' => 'Test message',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }

    public function test_feedback_message_has_max_length(): void
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
            ->postJson('/api/v1/feedback', [
                'type' => 'bug',
                'subject' => 'Test subject',
                'message' => str_repeat('a', 5001), // 5001 characters, exceeds max of 5000
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }
}
