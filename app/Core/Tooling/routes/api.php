<?php

declare(strict_types=1);

use App\Core\Tooling\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

/**
 * Core platform tooling API routes (System Observability).
 *
 * Registered under api/v1 by ToolingServiceProvider. Every route
 * requires authentication AND the specific system.* permission —
 * being logged in alone is not sufficient for this domain.
 */
Route::middleware(['auth:sanctum'])->prefix('system')->group(function () {
    Route::middleware('permission:system.logs.view')->group(function () {
        Route::get('/logs', [LogController::class, 'index']);
        Route::get('/logs/{filename}', [LogController::class, 'show']);
    });
});
