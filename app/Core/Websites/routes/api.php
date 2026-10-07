<?php

declare(strict_types=1);

use App\Core\Websites\Http\Controllers\WebsiteEngineController;
use App\Core\Websites\Http\Controllers\WebsiteNavigationController;
use App\Core\Websites\Http\Controllers\WebsitePublicController;
use Illuminate\Support\Facades\Route;

Route::get('/website/navigation/{code}', [WebsiteNavigationController::class, 'show'])
    ->middleware('surface:public')
    ->name('api.website.navigation.show');

Route::get('/website/public/pages/{slug}', [WebsitePublicController::class, 'page'])
    ->middleware('surface:public')
    ->where('slug', '.+')
    ->name('api.website.public.pages.show');

Route::middleware(['auth:sanctum', 'surface:api'])->group(function (): void {
    Route::get('/website/pages', [WebsiteEngineController::class, 'index'])
        ->middleware('permission:website.pages.view')
        ->name('api.website.pages.index');
    Route::get('/website/pages/{slug}', [WebsiteEngineController::class, 'show'])
        ->middleware('permission:website.pages.view')
        ->name('api.website.pages.show');
    Route::post('/website/pages', [WebsiteEngineController::class, 'store'])
        ->middleware('permission:website.pages.manage')
        ->name('api.website.pages.store');
    Route::put('/website/pages/{id}', [WebsiteEngineController::class, 'update'])
        ->middleware('permission:website.pages.manage')
        ->name('api.website.pages.update');
    Route::delete('/website/pages/{id}', [WebsiteEngineController::class, 'destroy'])
        ->middleware('permission:website.pages.manage')
        ->name('api.website.pages.destroy');

    Route::get('/website/menus', [WebsiteEngineController::class, 'listMenus'])
        ->middleware('permission:website.menus.view')
        ->name('api.website.menus.index');
    Route::get('/website/menus/{code}', [WebsiteEngineController::class, 'showMenu'])
        ->middleware('permission:website.menus.view')
        ->name('api.website.menus.show');
    Route::post('/website/menus', [WebsiteEngineController::class, 'storeMenu'])
        ->middleware('permission:website.menus.manage')
        ->name('api.website.menus.store');
    Route::put('/website/menus/{id}', [WebsiteEngineController::class, 'updateMenu'])
        ->middleware('permission:website.menus.manage')
        ->name('api.website.menus.update');
    Route::delete('/website/menus/{id}', [WebsiteEngineController::class, 'destroyMenu'])
        ->middleware('permission:website.menus.manage')
        ->name('api.website.menus.destroy');
});
