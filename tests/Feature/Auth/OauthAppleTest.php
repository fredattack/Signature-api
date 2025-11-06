<?php

namespace Tests\Feature\Auth;

use App\Models\OauthProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OauthAppleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_authenticate_with_apple_new_user(): void
    {
        // Create a mock Apple identity token
        // In real tests, you would properly encode this as JWT
        $appleUserId = 'apple_'.Str::random(10);
        $email = 'test@example.com';

        $identityToken = $this->createMockAppleToken($appleUserId, $email);

        $response = $this->postJson('/api/v1/auth/oauth/apple', [
            'identity_token' => $identityToken,
            'email' => $email,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'user',
                ],
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        // Verify OAuth provider was created
        $this->assertDatabaseHas('oauth_providers', [
            'provider' => 'apple',
            'provider_user_id' => $appleUserId,
        ]);
    }

    public function test_user_can_authenticate_with_apple_existing_user(): void
    {
        $email = 'test@example.com';
        $appleUserId = 'apple_'.Str::random(10);

        // Create existing user
        $user = User::create([
            'id' => Str::uuid(),
            'email' => $email,
            'password_hash' => Hash::make('Password123!'),
        ]);

        // Create existing OAuth provider
        OauthProvider::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'provider' => 'apple',
            'provider_user_id' => $appleUserId,
            'last_used_at' => now()->subDay(),
        ]);

        $identityToken = $this->createMockAppleToken($appleUserId, $email);

        $response = $this->postJson('/api/v1/auth/oauth/apple', [
            'identity_token' => $identityToken,
            'email' => $email,
        ]);

        $response->assertStatus(200);

        // Verify last_used_at was updated
        $this->assertDatabaseHas('oauth_providers', [
            'provider' => 'apple',
            'provider_user_id' => $appleUserId,
        ]);
    }

    public function test_user_cannot_authenticate_with_invalid_apple_token(): void
    {
        $response = $this->postJson('/api/v1/auth/oauth/apple', [
            'identity_token' => 'invalid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identity_token']);
    }

    public function test_user_cannot_authenticate_with_missing_apple_token(): void
    {
        $response = $this->postJson('/api/v1/auth/oauth/apple', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identity_token']);
    }

    /**
     * Create a mock Apple identity token
     * In production, this would be a properly signed JWT
     */
    private function createMockAppleToken(string $sub, string $email): string
    {
        $header = base64_encode(json_encode(['alg' => 'RS256', 'kid' => 'test']));
        $payload = base64_encode(json_encode([
            'sub' => $sub,
            'email' => $email,
            'iss' => 'https://appleid.apple.com',
        ]));
        $signature = base64_encode('mock-signature');

        return "{$header}.{$payload}.{$signature}";
    }
}
