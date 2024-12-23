<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorAuthController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/auth'], function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'updatePassword']);

        // Two Factor Authentication Routes
        Route::prefix('2fa')->group(function () {
            Route::post('/enable', [TwoFactorAuthController::class, 'enable']);
            Route::post('/confirm', [TwoFactorAuthController::class, 'confirm']);
            Route::post('/disable', [TwoFactorAuthController::class, 'disable']);
            Route::post('/verify', [TwoFactorAuthController::class, 'verify']);
        });
    });
});