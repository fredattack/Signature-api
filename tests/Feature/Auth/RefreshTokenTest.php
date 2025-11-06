<?php

namespace Tests\Feature\Auth;

use App\Models\Session;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_refresh_token_with_valid_refresh_token(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $refreshTokenData = $jwtService->generateRefreshToken($user);

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshTokenData['token'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                ],
            ]);
    }

    public function test_user_cannot_refresh_token_with_invalid_refresh_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'invalid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    public function test_user_cannot_refresh_token_with_expired_refresh_token(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $token = Str::random(64);
        Session::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'refresh_token' => hash('sha256', $token),
            'expires_at' => now()->subDay(), // Expired
        ]);

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }
}
