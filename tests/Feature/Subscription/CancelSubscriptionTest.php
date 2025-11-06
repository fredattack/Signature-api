<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Models\User;
use App\Services\JwtService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CancelSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_cancel_their_subscription(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => true,
            'premium_expires_at' => Carbon::now()->addMonth(),
        ]);

        $subscription = Subscription::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'stripe_subscription_id' => 'sub_test123',
            'status' => 'active',
            'plan_type' => 'monthly',
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addMonth(),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/v1/subscription/cancel', [
                'reason' => 'Testing cancellation',
                'feedback' => 'Great service, but need to cancel for now',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Subscription cancelled successfully. Your premium access will continue until the end of the current billing period.',
            ]);

        $subscription->refresh();
        $this->assertEquals('cancelled', $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
    }

    public function test_user_cannot_cancel_non_existent_subscription(): void
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
            ->postJson('/api/v1/subscription/cancel');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'No active subscription to cancel or subscription already cancelled.',
            ]);
    }

    public function test_user_cannot_cancel_already_cancelled_subscription(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => true,
            'premium_expires_at' => Carbon::now()->addMonth(),
        ]);

        $subscription = Subscription::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'status' => 'cancelled',
            'plan_type' => 'monthly',
            'current_period_start' => Carbon::now()->subMonth(),
            'current_period_end' => Carbon::now()->addDays(5),
            'cancelled_at' => Carbon::now()->subDays(10),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson('/api/v1/subscription/cancel');

        $response->assertStatus(400);
    }
}
