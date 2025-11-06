<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RefreshTokenAction;
use App\Actions\Auth\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthResource;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(
        RegisterRequest $request,
        RegisterUserAction $action
    ): AuthResource {
        /** @var array{email: string, password: string, first_name?: string, last_name?: string} $validated */
        $validated = $request->validated();
        $result = $action->execute($validated);

        return new AuthResource($result);
    }

    /**
     * Login user
     */
    public function login(
        LoginRequest $request,
        LoginUserAction $action
    ): AuthResource {
        /** @var array{email: string, password: string} $validated */
        $validated = $request->validated();
        $result = $action->execute($validated);

        return new AuthResource($result);
    }

    /**
     * Refresh access token
     */
    public function refresh(
        RefreshTokenRequest $request,
        RefreshTokenAction $action
    ): AuthResource {
        /** @var array{refresh_token: string} $validated */
        $validated = $request->validated();
        $result = $action->execute($validated);

        return new AuthResource($result);
    }

    /**
     * Logout user
     */
    public function logout(
        LogoutRequest $request,
        LogoutUserAction $action
    ): JsonResponse {
        /** @var array{refresh_token: string} $validated */
        $validated = $request->validated();
        $action->execute($validated);

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }
}
