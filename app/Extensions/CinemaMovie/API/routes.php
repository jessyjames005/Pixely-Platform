<?php

/**
 * CinemaMovie extension API routes.
 *
 * Registered under api/v1 by CinemaMovieServiceProvider, following
 * the same per-extension routing convention as other extensions.
 */

declare(strict_types=1);

use App\Extensions\CinemaMovie\Http\Controllers\Api\CinemaMovieController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use Illuminate\Support\Facades\Route;

Route::middleware([
    EnsureJsonApiMediaType::class,
    'auth:sanctum',
    'permission:cinema-movie.items.view',
])->group(function () {
    Route::get('/cinema-movie', [CinemaMovieController::class, 'index']);
});
