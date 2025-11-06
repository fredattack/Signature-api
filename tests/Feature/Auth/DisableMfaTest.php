<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DisableMfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_disable_mfa(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'mfa_enabled' => true,
            'mfa_secret' => 'test-secret',
        ]);

        $jwtService = app(JwtService::class);
        $accessTokenData = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessTokenData['token'])
            ->postJson('/api/v1/auth/mfa/disable', [
                'user_id' => $user->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'MFA has been disabled successfully.',
            ]);

        // Verify MFA was disabled
        $user->refresh();
        $this->assertFalse($user->mfa_enabled);
        $this->assertNull($user->mfa_secret);
    }

    public function test_user_cannot_disable_mfa_when_not_enabled(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'mfa_enabled' => false,
        ]);

        $jwtService = app(JwtService::class);
        $accessTokenData = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessTokenData['token'])
            ->postJson('/api/v1/auth/mfa/disable', [
                'user_id' => $user->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mfa']);
    }

    public function test_user_cannot_disable_mfa_without_authentication(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'mfa_enabled' => true,
            'mfa_secret' => 'test-secret',
        ]);

        $response = $this->postJson('/api/v1/auth/mfa/disable', [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(401);
    }
}
