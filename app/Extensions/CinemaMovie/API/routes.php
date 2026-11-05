<?php

/**
 * CinemaMovie extension API routes.
 *
 * Declared by CinemaMovie and registered under api/v1 by Core through the
 * ExtensionRoutesInterface capability.
 */

declare(strict_types=1);

use App\Extensions\CinemaMovie\Http\Controllers\Api\CinemaMovieController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use Illuminate\Support\Facades\Route;

Route::middleware([
    EnsureJsonApiMediaType::class,
    'auth:sanctum',
    'permission:cinema-movie.movies.view',
])->group(function () {
    Route::get('/cinema-movie', [CinemaMovieController::class, 'index']);
});
