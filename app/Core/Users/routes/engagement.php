<?php

declare(strict_types=1);

use App\Core\Users\Http\Controllers\UserEngagementController;
use Illuminate\Support\Facades\Route;

/** User-owned engagement endpoints. */
Route::middleware(['auth:sanctum', 'surface:user', 'throttle:60,1'])
    ->prefix('me')
    ->group(function (): void {
        Route::get('/favorites', [UserEngagementController::class, 'favorites']);
        Route::post('/favorites', [UserEngagementController::class, 'storeFavorite']);
        Route::delete('/favorites/{resourceType}/{resourceId}', [UserEngagementController::class, 'destroyFavorite'])
            ->where(['resourceType' => '[a-z][a-z0-9_.-]{0,63}', 'resourceId' => '[A-Za-z0-9:_-]{1,128}']);
        Route::get('/history', [UserEngagementController::class, 'history']);
        Route::post('/history', [UserEngagementController::class, 'storeHistory']);
    });
