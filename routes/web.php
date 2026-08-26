<?php

use Illuminate\Support\Facades\Route;

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
Route::get('/docs/api/openapi.yml', function () {
    return response()->file(
        base_path('docs/api/openapi.yml'),
        [
            'Content-Type' => 'application/yaml',
        ],
    );
})->name('api.openapi');

/**
 * Vue administration application.
 *
 * Laravel serves the Vue application entry point.
 * Vue Router handles the administration routes afterwards.
 */
Route::view('/admin/{any?}', 'app')
    ->where('any', '.*')
    ->name('admin.application');
