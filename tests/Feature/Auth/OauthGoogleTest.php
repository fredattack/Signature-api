<?php

namespace Tests\Feature\Auth;

use App\Models\OauthProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OauthGoogleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_authenticate_with_google_new_user(): void
    {
        // Create a mock Google identity token
        // In real tests, you would properly encode this as JWT
        $googleUserId = 'google_'.Str::random(10);
        $email = 'test@example.com';

        $identityToken = $this->createMockGoogleToken($googleUserId, $email);

        $response = $this->postJson('/api/v1/auth/oauth/google', [
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
            'provider' => 'google',
            'provider_user_id' => $googleUserId,
        ]);
    }

    public function test_user_can_authenticate_with_google_existing_user(): void
    {
        $email = 'test@example.com';
        $googleUserId = 'google_'.Str::random(10);

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
            'provider' => 'google',
            'provider_user_id' => $googleUserId,
            'last_used_at' => now()->subDay(),
        ]);

        $identityToken = $this->createMockGoogleToken($googleUserId, $email);

        $response = $this->postJson('/api/v1/auth/oauth/google', [
            'identity_token' => $identityToken,
            'email' => $email,
        ]);

        $response->assertStatus(200);

        // Verify last_used_at was updated
        $this->assertDatabaseHas('oauth_providers', [
            'provider' => 'google',
            'provider_user_id' => $googleUserId,
        ]);
    }

    public function test_google_oauth_links_to_existing_user_by_email(): void
    {
        $email = 'test@example.com';
        $googleUserId = 'google_'.Str::random(10);

        // Create existing user (not via OAuth)
        $existingUser = User::create([
            'id' => Str::uuid(),
            'email' => $email,
            'password_hash' => Hash::make('Password123!'),
        ]);

        $identityToken = $this->createMockGoogleToken($googleUserId, $email);

        $response = $this->postJson('/api/v1/auth/oauth/google', [
            'identity_token' => $identityToken,
            'email' => $email,
        ]);

        $response->assertStatus(200);

        // Verify OAuth provider was linked to existing user
        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $existingUser->id,
            'provider' => 'google',
            'provider_user_id' => $googleUserId,
        ]);

        // Should only have one user
        $this->assertEquals(1, User::where('email', $email)->count());
    }

    public function test_user_cannot_authenticate_with_invalid_google_token(): void
    {
        $response = $this->postJson('/api/v1/auth/oauth/google', [
            'identity_token' => 'invalid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identity_token']);
    }

    public function test_user_cannot_authenticate_with_missing_google_token(): void
    {
        $response = $this->postJson('/api/v1/auth/oauth/google', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identity_token']);
    }

    /**
     * Create a mock Google identity token
     * In production, this would be a properly signed JWT
     */
    private function createMockGoogleToken(string $sub, string $email): string
    {
        $header = base64_encode(json_encode(['alg' => 'RS256', 'kid' => 'test']));
        $payload = base64_encode(json_encode([
            'sub' => $sub,
            'email' => $email,
            'iss' => 'https://accounts.google.com',
        ]));
        $signature = base64_encode('mock-signature');

        return "{$header}.{$payload}.{$signature}";
    }
}
