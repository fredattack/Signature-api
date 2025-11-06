<?php

namespace App\Actions\Auth;

use App\Models\OauthProvider;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OauthGoogleAction
{
    public function __construct(
        private JwtService $jwtService
    ) {}

    /**
     * Authenticate user with Google OAuth
     *
     * @param  array{identity_token: string, email?: string, name?: string}  $data
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, user: User}
     *
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        // In production, verify the identity_token with Google's public keys
        // For now, we'll decode it to get the user info
        $googleUserId = $this->extractGoogleUserId($data['identity_token']);
        $email = $this->extractGoogleEmail($data['identity_token']);

        if (! $googleUserId || ! $email) {
            throw ValidationException::withMessages([
                'identity_token' => ['Invalid Google identity token.'],
            ]);
        }

        /** @var array{access_token: string, refresh_token: string, token_type: string, expires_in: int, user: User} */
        return DB::transaction(function () use ($googleUserId, $email): array {
            // Check if OAuth provider exists
            $oauthProvider = OauthProvider::where('provider', 'google')
                ->where('provider_user_id', $googleUserId)
                ->first();

            if ($oauthProvider) {
                // User exists, update last used
                $oauthProvider->update([
                    'last_used_at' => now(),
                ]);
                $user = $oauthProvider->user;
            } else {
                // Create new user or link to existing
                $user = User::where('email', $email)->first();

                if (! $user) {
                    // Create new user
                    $user = User::create([
                        'id' => Str::uuid(),
                        'email' => $email,
                        'password_hash' => null, // OAuth users don't need password
                        'email_verified_at' => now(), // Google verifies emails
                    ]);
                }

                // Create OAuth provider record
                OauthProvider::create([
                    'id' => Str::uuid(),
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'provider_user_id' => $googleUserId,
                    'last_used_at' => now(),
                ]);
            }

            // Ensure user is not null
            assert($user instanceof User);

            // Generate tokens
            $accessToken = $this->jwtService->generateAccessToken($user);
            $refreshTokenData = $this->jwtService->generateRefreshToken($user);

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshTokenData['token'],
                'token_type' => 'Bearer',
                'expires_in' => (int) config('jwt.access_token_ttl', 900),
                'user' => $user,
            ];
        });
    }

    /**
     * Extract Google user ID from identity token
     * In production, this should verify the JWT signature with Google's public keys
     */
    private function extractGoogleUserId(string $identityToken): ?string
    {
        try {
            // Decode JWT without verification (for development)
            // In production, use Firebase JWT to verify with Google's public keys
            $parts = explode('.', $identityToken);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            return $payload['sub'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract email from Google identity token
     */
    private function extractGoogleEmail(string $identityToken): ?string
    {
        try {
            $parts = explode('.', $identityToken);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            return $payload['email'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
