<?php

declare(strict_types=1);

use App\Core\Registry\Http\Controllers\RegistryPackageCatalogController;
use App\Core\Registry\Http\Controllers\RegistryReleaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['surface:public', 'throttle:registry-public'])->group(function (): void {
    Route::get('/packages', [RegistryPackageCatalogController::class, 'index'])
        ->name('api.registry.packages.index');
    Route::get('/releases', [RegistryReleaseController::class, 'index'])
        ->name('api.registry.releases.index');
    Route::get('/releases/resolve', [RegistryReleaseController::class, 'resolve'])
        ->name('api.registry.releases.resolve');
});
