<?php

declare(strict_types=1);

use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Route;

/**
 * Public web routes.
 */

Route::middleware('surface:public')->get('/', function () {
    return view('app');
})->name('public.home');

/**
 * Swagger UI documentation.
 *
 * The Swagger UI interface consumes the generated OpenAPI specification.
 */
Route::view('/docs/api', 'api.swagger')
    ->name('api.documentation');

/**
 * Generated OpenAPI specification.
 *
 * The specification is generated from the platform API definitions
 * and exposed read-only for Swagger UI.
 */
Scramble::registerJsonSpecificationRoute('docs/api/openapi.json')
    ->name('api.openapi');

/**
 * Vue administration application.
 *
 * Laravel serves the Vue application entry point.
 * Vue Router handles the administration routes afterwards.
 */
Route::view('/admin/{any?}', 'app')
    ->middleware(['auth', 'surface:admin', 'permission:system.admin.access'])
    ->where('any', '.*')
    ->name('admin.application');

/**
 * Vue administration login page.
 *
 * Served outside /admin so it stays accessible when the
 * administration routes require authentication.
 */
Route::view('/login', 'app')
    ->name('login.application');

/**
 * User Space application.
 *
 * Serves the Vue application for authenticated users.
 * Step 3 of the progressive migration plan.
 */
Route::view('/account/{any?}', 'app')
    ->middleware(['auth', 'surface:user'])
    ->where('any', '.*')
    ->name('user.application');
/**
 * Public Vue application fallback.
 *
 * Reserved platform paths are excluded so administration, user space,
 * API and documentation routes keep their dedicated handlers.
 */
Route::view('/{path}', 'app')
    ->middleware('surface:public')
    ->where('path', '(?!(?:admin|account|api|docs|login|sanctum|up)(?:/|$)).*');
