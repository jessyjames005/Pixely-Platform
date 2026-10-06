<?php


declare(strict_types=1);

namespace App\Core\Websites\Http\Routes;

use App\Core\Websites\Http\Controllers\WebsiteEngineController;
use Illuminate\Support\Facades\Route;

/**
 * Routes for the Website Engine.
 *
 * Provides REST API routes for managing website pages and menus.
 */
function websiteEngineRoutes(): void
{
    // Pages
    Route::get('/website/pages', [WebsiteEngineController::class, 'index'])->middleware('api');
    Route::get('/website/pages/{slug}', [WebsiteEngineController::class, 'show'])->middleware('api');
    Route::post('/website/pages', [WebsiteEngineController::class, 'store'])->middleware('api');
    Route::put('/website/pages/{id}', [WebsiteEngineController::class, 'update'])->middleware('api');
    Route::delete('/website/pages/{id}', [WebsiteEngineController::class, 'destroy'])->middleware('api');

    // Menus
    Route::get('/website/menus', [WebsiteEngineController::class, 'listMenus'])->middleware('api');
    Route::get('/website/menus/{code}', [WebsiteEngineController::class, 'showMenu'])->middleware('api');
    Route::post('/website/menus', [WebsiteEngineController::class, 'storeMenu'])->middleware('api');
    Route::put('/website/menus/{id}', [WebsiteEngineController::class, 'updateMenu'])->middleware('api');
    Route::delete('/website/menus/{id}', [WebsiteEngineController::class, 'destroyMenu'])->middleware('api');
}

// Register routes when application is loaded
Route::middleware('api')->group(function () {
    websiteEngineRoutes();
});