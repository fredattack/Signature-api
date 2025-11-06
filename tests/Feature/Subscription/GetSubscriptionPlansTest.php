<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetSubscriptionPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_subscription_plans(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/subscription/plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'price',
                        'currency',
                        'interval',
                        'features',
                        'description',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data'); // free, monthly, annual
    }

    public function test_plans_include_all_required_information(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/subscription/plans');

        $response->assertStatus(200);

        $plans = $response->json('data');

        // Check that each plan has necessary features
        foreach ($plans as $plan) {
            $this->assertArrayHasKey('features', $plan);
            $this->assertIsArray($plan['features']);
        }
    }
}
