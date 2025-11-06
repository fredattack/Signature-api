<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\DisableMfaAction;
use App\Actions\Auth\ForgotPasswordAction;
use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\OauthAppleAction;
use App\Actions\Auth\OauthGoogleAction;
use App\Actions\Auth\RefreshTokenAction;
use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\SetupMfaAction;
use App\Actions\Auth\VerifyMfaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DisableMfaRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\OauthAppleRequest;
use App\Http\Requests\Auth\OauthGoogleRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SetupMfaRequest;
use App\Http\Requests\Auth\VerifyMfaRequest;
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

    /**
     * Send password reset link
     */
    public function forgotPassword(
        ForgotPasswordRequest $request,
        ForgotPasswordAction $action
    ): JsonResponse {
        /** @var array{email: string} $validated */
        $validated = $request->validated();
        $result = $action->execute($validated);

        return response()->json($result);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(
        ResetPasswordRequest $request,
        ResetPasswordAction $action
    ): JsonResponse {
        /** @var array{token: string, email: string, password: string} $validated */
        $validated = $request->validated();
        $result = $action->execute($validated);

        return response()->json($result);
    }

    /**
     * Setup MFA for user (generate secret and QR code)
     */
    public function setupMfa(
        SetupMfaRequest $request,
        SetupMfaAction $action
    ): JsonResponse {
        /** @var array{user_id: string} $validated */
        $validated = $request->validated();
        $result = $action->execute($validated);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Verify MFA code and enable MFA
     */
    public function verifyMfa(
        VerifyMfaRequest $request,
        VerifyMfaAction $action
    ): JsonResponse {
        /** @var array{user_id: string, code: string} $validated */
        $validated = $request->validated();
        $result = $action->execute($validated);

        return response()->json($result);
    }

    /**
     * Disable MFA for user
     */
    public function disableMfa(
        DisableMfaRequest $request,
        DisableMfaAction $action
    ): JsonResponse {
        /** @var array{user_id: string} $validated */
        $validated = $request->validated();
        $result = $action->execute($validated);

        return response()->json($result);
    }

    /**
     * Authenticate with Apple OAuth
     */
    public function oauthApple(
        OauthAppleRequest $request,
        OauthAppleAction $action
    ): AuthResource {
        /** @var array{identity_token: string, email?: string, name?: string} $validated */
        $validated = $request->validated();
        $result = $action->execute($validated);

        return new AuthResource($result);
    }

    /**
     * Authenticate with Google OAuth
     */
    public function oauthGoogle(
        OauthGoogleRequest $request,
        OauthGoogleAction $action
    ): AuthResource {
        /** @var array{identity_token: string, email?: string, name?: string} $validated */
        $validated = $request->validated();
        $result = $action->execute($validated);

        return new AuthResource($result);
    }
}
