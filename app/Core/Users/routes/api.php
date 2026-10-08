<?php

/**
 * Core user management API routes.
 *
 * Registered under api/v1 by UserServiceProvider, following the
 * same per-module routing convention as extensions and Core Auth.
 *
 * All operations require an authenticated administrator.
 */

declare(strict_types=1);

use App\Core\Users\Http\Controllers\UserController;
use App\Core\Users\Http\Controllers\ProfileController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\Relationships;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use LaravelJsonApi\Laravel\Routing\Route as JsonApiRoutingRoute;

JsonApiRoute::server('v1')
    ->middleware(EnsureJsonApiMediaType::class)
    ->resources(function (ResourceRegistrar $server): void {
        $server->resource('users', UserController::class)
            ->middleware('auth:sanctum')
            ->relationships(function (Relationships $relationships): void {
                $relationships->hasMany('roles')->only('show', 'update');
            });

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/profile', [ProfileController::class, 'show'])
                ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
            Route::put('/profile', [ProfileController::class, 'update'])
                ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
            Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])
                ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
        });
    });

/**
 * Generic User Space engagement endpoints.
 *
 * These resources intentionally use stable resource type/id pairs so Core
 * remains independent from extension-specific Eloquent models.
 */
Route::middleware(['auth:sanctum', 'surface:user'])->prefix('me')->group(function (): void {
    Route::get('/favorites', [\App\Core\Users\Http\Controllers\UserEngagementController::class, 'favorites']);
    Route::post('/favorites', [\App\Core\Users\Http\Controllers\UserEngagementController::class, 'storeFavorite']);
    Route::delete('/favorites/{resourceType}/{resourceId}', [\App\Core\Users\Http\Controllers\UserEngagementController::class, 'destroyFavorite']);
    Route::get('/history', [\App\Core\Users\Http\Controllers\UserEngagementController::class, 'history']);
    Route::post('/history', [\App\Core\Users\Http\Controllers\UserEngagementController::class, 'storeHistory']);
});
