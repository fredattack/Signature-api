<?php

namespace App\Actions\Auth;

use App\Services\JwtService;
use Illuminate\Validation\ValidationException;

class RefreshTokenAction
{
    public function __construct(
        private readonly JwtService $jwtService
    ) {}

    /**
     * Refresh access token using refresh token
     *
     * @param  array{refresh_token: string}  $data
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     *
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        $session = $this->jwtService->verifyRefreshToken($data['refresh_token']);

        if (! $session) {
            throw ValidationException::withMessages([
                'refresh_token' => ['The refresh token is invalid or expired.'],
            ]);
        }

        $user = $session->user;

        if (! $user) {
            throw ValidationException::withMessages([
                'refresh_token' => ['User not found for this token.'],
            ]);
        }

        // Generate new access token
        $accessToken = $this->jwtService->generateAccessToken($user);

        // Generate new refresh token and revoke old one
        $this->jwtService->revokeRefreshToken($data['refresh_token']);

        $newRefreshTokenData = $this->jwtService->generateRefreshToken(
            $user,
            request()->ip(),
            request()->userAgent()
        );

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshTokenData['token'],
            'expires_in' => (int) config('jwt.access_token_ttl', 900),
        ];
    }
}
