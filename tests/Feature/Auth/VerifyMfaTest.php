<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OTPHP\TOTP;
use Tests\TestCase;

class VerifyMfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_mfa_with_valid_code(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        // Generate and save MFA secret
        $totp = TOTP::generate();
        $secret = $totp->getSecret();
        $user->update(['mfa_secret' => $secret]);

        // Generate valid code
        $validCode = $totp->now();

        $jwtService = app(JwtService::class);
        $accessTokenData = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessTokenData['token'])
            ->postJson('/api/v1/auth/mfa/verify', [
                'user_id' => $user->id,
                'code' => $validCode,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'MFA has been enabled successfully.',
            ]);

        // Verify MFA was enabled
        $user->refresh();
        $this->assertTrue($user->mfa_enabled);
    }

    public function test_user_cannot_verify_mfa_with_invalid_code(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        // Generate and save MFA secret
        $totp = TOTP::generate();
        $secret = $totp->getSecret();
        $user->update(['mfa_secret' => $secret]);

        $jwtService = app(JwtService::class);
        $accessTokenData = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessTokenData['token'])
            ->postJson('/api/v1/auth/mfa/verify', [
                'user_id' => $user->id,
                'code' => '000000', // Invalid code
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        // Verify MFA was not enabled
        $user->refresh();
        $this->assertFalse($user->mfa_enabled);
    }

    public function test_user_cannot_verify_mfa_without_secret(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
        ]);

        $jwtService = app(JwtService::class);
        $accessTokenData = $jwtService->generateAccessToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$accessTokenData['token'])
            ->postJson('/api/v1/auth/mfa/verify', [
                'user_id' => $user->id,
                'code' => '123456',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mfa']);
    }

    public function test_user_cannot_verify_mfa_without_authentication(): void
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => 'test@example.com',
            'password_hash' => Hash::make('Password123!'),
            'mfa_secret' => 'test-secret',
        ]);

        $response = $this->postJson('/api/v1/auth/mfa/verify', [
            'user_id' => $user->id,
            'code' => '123456',
        ]);

        $response->assertStatus(401);
    }
}
