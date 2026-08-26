<?php

declare(strict_types=1);

use App\Core\Users\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/**
 * Core user management API routes.
 *
 * Registered under api/v1 by UserServiceProvider, following the
 * same per-module routing convention as extensions and Core Auth.
 *
 * All operations require an authenticated administrator.
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
