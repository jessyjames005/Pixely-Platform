<?php

declare(strict_types=1);

use App\Core\Extensions\Http\Controllers\ExtensionController;
use App\Core\Extensions\Http\Controllers\ExtensionInstallController;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['auth:sanctum', 'permission:system.extensions.install'])->prefix('extensions')->group(function () {
    Route::post('/install', [ExtensionInstallController::class, 'install']);
    Route::post('/{id}/update', [ExtensionInstallController::class, 'update']);
    Route::delete('/{id}', [ExtensionInstallController::class, 'destroy']);
});
