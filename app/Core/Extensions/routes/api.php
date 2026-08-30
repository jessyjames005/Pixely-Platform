<?php

declare(strict_types=1);

use App\Core\Extensions\Http\Controllers\ExtensionController;
use Illuminate\Support\Facades\Route;

/**
 * Core extension management API routes.
 *
 * Install/update/uninstall are intentionally NOT in this file —
 * see ExtensionInstallController, gated by system.extensions.install.
 */
Route::middleware(['auth:sanctum', 'permission:system.extensions.view'])->prefix('extensions')->group(function () {
    Route::get('/', [ExtensionController::class, 'index']);
    Route::get('/{id}', [ExtensionController::class, 'show']);
    Route::get('/{id}/config', [ExtensionController::class, 'showConfig']);
});

Route::middleware(['auth:sanctum', 'permission:system.extensions.manage'])->prefix('extensions')->group(function () {
    Route::post('/{id}/enable', [ExtensionController::class, 'enable']);
    Route::post('/{id}/disable', [ExtensionController::class, 'disable']);
    Route::put('/{id}/config', [ExtensionController::class, 'updateConfig']);
});
