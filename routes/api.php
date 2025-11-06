<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Auth Routes (with rate limiting)
    Route::prefix('auth')->middleware('throttle:60,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Password Reset
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        // OAuth Authentication
        Route::post('/oauth/apple', [AuthController::class, 'oauthApple']);
        Route::post('/oauth/google', [AuthController::class, 'oauthGoogle']);
    });

    // Protected Routes (require JWT authentication)
    Route::middleware(['auth.jwt', 'throttle:60,1'])->group(function () {
        // MFA Management
        Route::prefix('auth/mfa')->group(function () {
            Route::post('/setup', [AuthController::class, 'setupMfa']);
            Route::post('/verify', [AuthController::class, 'verifyMfa']);
            Route::post('/disable', [AuthController::class, 'disableMfa']);
        });
    });
});
