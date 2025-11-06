<?php

namespace Tests\Feature\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Services\JwtService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GetUserEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_events(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        // Create some events
        AnalyticsEvent::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'event_type' => 'signature.created',
            'event_data' => ['signature_id' => Str::uuid()],
            'created_at' => Carbon::now(),
        ]);

        AnalyticsEvent::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'event_type' => 'wallpaper.generated',
            'event_data' => ['wallpaper_id' => Str::uuid()],
            'created_at' => Carbon::now()->subHours(2),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/analytics/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'event_type',
                        'event_data',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_filter_events_by_type(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        AnalyticsEvent::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'event_type' => 'signature.created',
            'event_data' => [],
            'created_at' => Carbon::now(),
        ]);

        AnalyticsEvent::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'event_type' => 'wallpaper.generated',
            'event_data' => [],
            'created_at' => Carbon::now(),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/analytics/events?event_type=signature.created');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_type', 'signature.created');
    }

    public function test_user_can_filter_events_by_date_range(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        AnalyticsEvent::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'event_type' => 'signature.created',
            'event_data' => [],
            'created_at' => Carbon::now()->subDays(10),
        ]);

        AnalyticsEvent::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'event_type' => 'signature.created',
            'event_data' => [],
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user);

        $startDate = Carbon::now()->subDays(5)->toDateString();

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/analytics/events?start_date='.$startDate);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_view_other_users_events(): void
    {
        $user1 = User::create([
            'id' => Str::uuid(),
            'email' => 'user1@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $user2 = User::create([
            'id' => Str::uuid(),
            'email' => 'user2@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        AnalyticsEvent::create([
            'id' => Str::uuid(),
            'user_id' => $user2->id,
            'event_type' => 'signature.created',
            'event_data' => [],
            'created_at' => Carbon::now(),
        ]);

        $jwtService = app(JwtService::class);
        $accessToken = $jwtService->generateAccessToken($user1);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/v1/analytics/events');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data'); // User1 should not see User2's events
    }
}
