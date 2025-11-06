<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    public function __construct(
        private readonly JwtService $jwtService
    ) {}

    /**
     * Login user and return tokens
     *
     * @param  array{email: string, password: string}  $data
     * @return array{user: User, access_token: string, refresh_token: string, expires_in: int}
     *
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->password_hash || ! Hash::check($data['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

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
