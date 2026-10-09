<?php

declare(strict_types=1);

use App\Core\Auth\Http\Controllers\AuthController;
use App\Core\Auth\Http\Controllers\PasswordController;
use App\Core\Auth\Http\Controllers\PasswordResetController;
use App\Core\Auth\Http\Controllers\TwoFactorChallengeController;
use App\Core\Auth\Http\Controllers\TwoFactorController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use LaravelJsonApi\Laravel\Routing\Route as JsonApiRoutingRoute;

JsonApiRoute::server('v1')
    ->middleware(EnsureJsonApiMediaType::class)
    ->resources(function (ResourceRegistrar $server): void {
        // Public: reachable before a session exists.
        Route::middleware('web')->group(function (): void {
            Route::post('/auth/login', [AuthController::class, 'login'])
                ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');

            Route::post('/auth/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
                ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');

            Route::middleware('throttle:auth-password-reset')->group(function (): void {
                Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgot'])
                    ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');

                Route::post('/auth/reset-password', [PasswordResetController::class, 'reset'])
                    ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
            });
        });

        // Authenticated.
        Route::middleware(['web', 'auth:sanctum'])->group(function (): void {
            Route::post('/auth/logout', [AuthController::class, 'logout'])
                ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
            Route::get('/auth/me', [AuthController::class, 'me'])
                ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');

            Route::middleware('throttle:auth-sensitive')->group(function (): void {
                Route::put('/auth/password', [PasswordController::class, 'update'])
                    ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');

                Route::get('/auth/two-factor', [TwoFactorController::class, 'show'])
                    ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
                Route::post('/auth/two-factor', [TwoFactorController::class, 'store'])
                    ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
                Route::post('/auth/two-factor/confirm', [TwoFactorController::class, 'confirm'])
                    ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
                Route::post('/auth/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
                    ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
                Route::delete('/auth/two-factor', [TwoFactorController::class, 'destroy'])
                    ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
            });
        });
    });
