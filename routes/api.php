<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SignatureController;
use App\Http\Controllers\Api\V1\WallpaperController;
use App\Http\Controllers\Api\V1\WallpaperTemplateController;
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

        // Signatures Management
        Route::prefix('signatures')->group(function () {
            Route::get('/', [SignatureController::class, 'index']);
            Route::post('/', [SignatureController::class, 'store']);
            Route::get('/{id}', [SignatureController::class, 'show']);
            Route::post('/{id}', [SignatureController::class, 'update']); // POST for file upload support
            Route::delete('/{id}', [SignatureController::class, 'destroy']);
        });

        // Wallpaper Templates (Browse)
        Route::prefix('wallpaper-templates')->group(function () {
            Route::get('/', [WallpaperTemplateController::class, 'index']);
            Route::get('/{id}', [WallpaperTemplateController::class, 'show']);
        });

        // Wallpapers Management
        Route::prefix('wallpapers')->group(function () {
            Route::get('/', [WallpaperController::class, 'index']);
            Route::post('/', [WallpaperController::class, 'store']); // Generate wallpaper
            Route::get('/{id}', [WallpaperController::class, 'show']);
            Route::delete('/{id}', [WallpaperController::class, 'destroy']);
        });
    });
});
