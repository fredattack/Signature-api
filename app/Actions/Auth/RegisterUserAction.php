<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterUserAction
{
    public function __construct(
        private readonly JwtService $jwtService
    ) {}

    /**
     * Register a new user and return tokens
     *
     * @param  array{email: string, password: string, first_name?: string, last_name?: string}  $data
     * @return array{user: User, access_token: string, refresh_token: string, expires_in: int}
     */
    public function execute(array $data): array
    {
        $user = User::create([
            'id' => Str::uuid(),
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'is_premium' => false,
            'mfa_enabled' => false,
            'locale' => 'en',
            'timezone' => 'UTC',
        ]);

        $accessToken = $this->jwtService->generateAccessToken($user);

        $refreshTokenData = $this->jwtService->generateRefreshToken(
            $user,
            request()->ip(),
            request()->userAgent()
        );

        return [
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenData['token'],
            'expires_in' => (int) config('jwt.access_token_ttl', 900),
        ];
    }
}
