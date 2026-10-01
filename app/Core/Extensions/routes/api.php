<?php

declare(strict_types=1);

use App\Core\Extensions\Http\Controllers\ExtensionController;
use App\Core\Extensions\Http\Controllers\ExtensionInstallController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;

JsonApiRoute::server('v1')
    ->middleware(EnsureJsonApiMediaType::class)
    ->resources(function (): void {
        Route::middleware(['auth:sanctum', 'permission:system.extensions.view'])
            ->prefix('extensions')
            ->group(function (): void {
            Route::get('/', [ExtensionController::class, 'index']);
            Route::get('/{id}', [ExtensionController::class, 'show']);
            Route::get('/{id}/config', [ExtensionController::class, 'showConfig']);
        });

        Route::middleware(['auth:sanctum', 'permission:system.extensions.manage'])
            ->prefix('extensions')
            ->group(function (): void {
            Route::post('/{id}/enable', [ExtensionController::class, 'enable']);
            Route::post('/{id}/disable', [ExtensionController::class, 'disable']);
            Route::put('/{id}/config', [ExtensionController::class, 'updateConfig']);
        });

        Route::middleware(['auth:sanctum', 'permission:system.extensions.install'])
            ->prefix('extensions')
            ->group(function (): void {
            Route::post('/install', [ExtensionInstallController::class, 'install']);
            Route::post('/{id}/update', [ExtensionInstallController::class, 'update']);
        });
    });

Route::middleware(['auth:sanctum', 'permission:system.extensions.install'])
    ->prefix('extensions')
    ->group(function (): void {
        Route::delete('/{id}', [ExtensionInstallController::class, 'destroy']);
    });
