<?php

namespace Tests\Feature\Analytics;

use App\Models\Signature;
use App\Models\User;
use App\Services\JwtService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetUserStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_stats(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => false,
        ]);

        // Create some signatures
        Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Test Signature 1',
            'image_path' => 'signatures/test1.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 10000,
            'mime_type' => 'image/png',
            'created_at' => Carbon::now(),
        ]);

        Signature::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Test Signature 2',
            'image_path' => 'signatures/test2.png',
            'image_width' => 500,
            'image_height' => 200,
            'file_size' => 10000,
            'mime_type' => 'image/png',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/analytics/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'signatures' => [
                    'total',
                    'created_this_month',
                    'most_recent',
                ],
                'wallpapers' => [
                    'total',
                    'generated_this_month',
                    'most_used_template',
                ],
                'subscription' => [
                    'is_premium',
                    'plan_type',
                    'status',
                ],
                'activity' => [
                    'total_events',
                    'events_this_week',
                    'most_common_actions',
                ],
            ])
            ->assertJson([
                'signatures' => [
                    'total' => 2,
                    'created_this_month' => 2,
                ],
                'wallpapers' => [
                    'total' => 0,
                ],
                'subscription' => [
                    'is_premium' => false,
                    'plan_type' => 'free',
                ],
            ]);
    }

    public function test_premium_user_sees_correct_subscription_stats(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'premium@example.com',
            'password_hash' => Hash::make('Password123!'),
            'is_premium' => true,
            'premium_expires_at' => Carbon::now()->addMonth(),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/analytics/stats');

        $response->assertStatus(200)
            ->assertJson([
                'subscription' => [
                    'is_premium' => true,
                ],
            ])
            ->assertJsonStructure([
                'subscription' => [
                    'expires_at',
                    'days_remaining',
                ],
            ]);
    }
}
