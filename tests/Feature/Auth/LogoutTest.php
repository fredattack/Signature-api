<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_logout_with_valid_refresh_token(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $refreshTokenData = $jwtService->generateRefreshToken($user);

        $response = $this->postJson('/api/v1/auth/logout', [
            'refresh_token' => $refreshTokenData['token'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out.',
            ]);

        // Verify session is deleted
        $this->assertDatabaseMissing('sessions', [
            'id' => $refreshTokenData['session']->id,
        ]);
    }

    public function test_user_cannot_logout_with_invalid_refresh_token(): void
    {
        $response = $this->postJson('/api/v1/auth/logout', [
            'refresh_token' => 'invalid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }
}
