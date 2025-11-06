<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use App\Services\JwtService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetSubscriptionStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_can_view_their_status(): void
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
            ->getJson('/api/v1/subscription/status');

        $response->assertStatus(200)
            ->assertJson([
                'is_premium' => false,
                'plan_type' => 'free',
            ]);
    }

    public function test_premium_user_can_view_their_status(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => true,
            'premium_expires_at' => Carbon::now()->addMonth(),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/subscription/status');

        $response->assertStatus(200)
            ->assertJson([
                'is_premium' => true,
            ])
            ->assertJsonStructure([
                'is_premium',
                'plan_type',
                'premium_expires_at',
                'access_details' => [
                    'has_access',
                    'expires_at',
                    'days_remaining',
                ],
            ]);
    }

    public function test_expired_premium_user_sees_correct_status(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => true,
            'premium_expires_at' => Carbon::now()->subDay(), // Expired yesterday
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/subscription/status');

        $response->assertStatus(200)
            ->assertJson([
                'is_premium' => true, // Flag is still true
                'plan_type' => 'free', // But effective plan is free
                'access_details' => [
                    'has_access' => false, // No access since expired
                ],
            ]);
    }
}
