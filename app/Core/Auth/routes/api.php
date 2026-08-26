<?php

declare(strict_types=1);

use App\Core\Auth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/**
 * Core authentication API routes.
 *
 * Registered under api/v1 by AuthServiceProvider, following
 * the same per-module routing convention as extensions.
 */
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});
