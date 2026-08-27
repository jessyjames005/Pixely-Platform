<?php

use Illuminate\Support\Facades\Route;
use Dedoc\Scramble\Scramble;

/**
 * Public web routes.
 */
Route::get('/', function () {
    return view('welcome');
});

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
