<?php

namespace App\Actions\Auth;

use App\Services\JwtService;
use Illuminate\Validation\ValidationException;

class LogoutUserAction
{
    public function __construct(
        private readonly JwtService $jwtService
    ) {}

    /**
     * Logout user by revoking refresh token
     *
     * @param  array{refresh_token: string}  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): bool
    {
        $revoked = $this->jwtService->revokeRefreshToken($data['refresh_token']);

        if (! $revoked) {
            throw ValidationException::withMessages([
                'refresh_token' => ['The refresh token is invalid.'],
            ]);
        }

        return true;
    }
}
