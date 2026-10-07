<?php

/**
 * Core Website Engine API routes.
 *
 * Registered under api/v1 by WebsiteEngineServiceProvider,
 * following the same per-module routing convention as other
 * Core modules (Auth, Users, Roles, Extensions, Tooling).
 *
 * Every operation requires authentication AND the matching
 * website.* permission — being logged in alone is not
 * sufficient for this domain.
 */

declare(strict_types=1);

use App\Core\Websites\Http\Controllers\WebsiteEngineController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'surface:api'])->prefix('website')->group(function (): void {
    Route::middleware('permission:website.pages.view')->group(function (): void {
        Route::get('/pages', [WebsiteEngineController::class, 'index']);
        Route::get('/pages/{slug}', [WebsiteEngineController::class, 'show']);
    });

    Route::middleware('permission:website.pages.manage')->group(function (): void {
        Route::post('/pages', [WebsiteEngineController::class, 'store']);
        Route::put('/pages/{id}', [WebsiteEngineController::class, 'update']);
        Route::delete('/pages/{id}', [WebsiteEngineController::class, 'destroy']);
    });

    Route::middleware('permission:website.menus.view')->group(function (): void {
        Route::get('/menus', [WebsiteEngineController::class, 'listMenus']);
        Route::get('/menus/{code}', [WebsiteEngineController::class, 'showMenu']);
    });

    Route::middleware('permission:website.menus.manage')->group(function (): void {
        Route::post('/menus', [WebsiteEngineController::class, 'storeMenu']);
        Route::put('/menus/{id}', [WebsiteEngineController::class, 'updateMenu']);
        Route::delete('/menus/{id}', [WebsiteEngineController::class, 'destroyMenu']);
    });
});