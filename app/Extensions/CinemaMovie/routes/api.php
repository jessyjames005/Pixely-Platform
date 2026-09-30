<?php

declare(strict_types=1);

use App\Extensions\CinemaMovie\Http\Controllers\Api\CinemaMovieController;
use Illuminate\Support\Facades\Route;

/**
 * CinemaMovie extension API routes.
 *
 * Registered under api/v1 by CinemaMovieServiceProvider, following
 * the same per-extension routing convention as other extensions.
 */
Route::middleware(['auth:sanctum', 'permission:cinema-movie.items.view'])->group(function () {
    Route::get('/cinema-movie', [CinemaMovieController::class, 'index']);
});
