<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SetupMfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_setup_mfa(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessTokenData = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessTokenData['token'])
            ->postJson('/api/v1/auth/mfa/setup', [
                'user_id' => $user->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'secret',
                    'qr_code',
                ],
            ]);

        // Verify secret was saved
        $user->refresh();
        $this->assertNotNull($user->mfa_secret);
        $this->assertFalse($user->mfa_enabled); // Not enabled yet
    }

    public function test_user_cannot_setup_mfa_without_authentication(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/mfa/setup', [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_user_cannot_setup_mfa_for_nonexistent_user(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessTokenData = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessTokenData['token'])
            ->postJson('/api/v1/auth/mfa/setup', [
                'user_id' => Str::uuid(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }
}
