<?php

/** CinemaMovie API and public routes. */

declare(strict_types=1);

use App\Extensions\CinemaMovie\Http\Controllers\Api\CinemaMovieController;
use App\Extensions\CinemaMovie\Http\Controllers\PublicMovieController;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/**
 * Register public endpoints for movie pages, trending, and TMDB v3 API proxy calls.
 *
 * The TMDB proxy is read-only and rate-limited so the server-side TMDB API key
 * cannot be abused. Numeric identifiers are constrained at the route level so
 * invalid input is rejected with a 404 instead of reaching the controller.
 */
Route::prefix('public')->group(function (): void {
    Route::get('/movies/{slug}', [PublicMovieController::class, 'show']);
    Route::get('/trending-movies', [PublicMovieController::class, 'trending']);

    Route::middleware('throttle:120,1')->group(function (): void {
        Route::get('/tmdb/popular-movies', [PublicMovieController::class, 'popularMovies']);
        Route::get('/tmdb/top-rated-movies', [PublicMovieController::class, 'topRatedMovies']);
        Route::get('/tmdb/upcoming-movies', [PublicMovieController::class, 'upcomingMovies']);
        Route::get('/tmdb/now-playing-movies', [PublicMovieController::class, 'nowPlayingMovies']);
        Route::get('/tmdb/popular-people', [PublicMovieController::class, 'popularPeople']);
        Route::get('/tmdb/person/{id}', [PublicMovieController::class, 'personDetails'])->whereNumber('id');
        Route::get('/tmdb/person/{id}/movie-credits', [PublicMovieController::class, 'personMovieCredits'])->whereNumber('id');
        Route::get('/tmdb/movie/{id}', [PublicMovieController::class, 'movieDetails'])->whereNumber('id');
        Route::get('/tmdb/movie/{id}/credits', [PublicMovieController::class, 'movieCredits'])->whereNumber('id');
        Route::get('/tmdb/search', [PublicMovieController::class, 'search']);
    });
});

/**
 * Register JSON:API resources for cinema movies, genres, and translations with permission middleware.
 */
JsonApiRoute::server('v1')->middleware('auth:sanctum')->resources(
    function (ResourceRegistrar $server): void {
        $server->resource('cinema-movies', CinemaMovieController::class)->middleware([
            'index' => 'permission:cinema-movie.movies.view',
            'show' => 'permission:cinema-movie.movies.view',
            'store' => 'permission:cinema-movie.movies.manage',
            'update' => 'permission:cinema-movie.movies.manage',
            'destroy' => 'permission:cinema-movie.movies.delete',
        ]);
        $server->resource('cinema-genres', CinemaMovieController::class)->middleware([
            'index' => 'permission:cinema-movie.movies.view',
            'show' => 'permission:cinema-movie.movies.view',
            'store' => 'permission:cinema-movie.movies.manage',
            'update' => 'permission:cinema-movie.movies.manage',
            'destroy' => 'permission:cinema-movie.movies.delete',
        ]);
        $server->resource('cinema-movie-translations', CinemaMovieController::class)->middleware([
            'index' => 'permission:cinema-movie.movies.view',
            'show' => 'permission:cinema-movie.movies.view',
            'store' => 'permission:cinema-movie.movies.manage',
            'update' => 'permission:cinema-movie.movies.manage',
            'destroy' => 'permission:cinema-movie.movies.delete',
        ]);
        $server->resource('cinema-genre-translations', CinemaMovieController::class)->middleware([
            'index' => 'permission:cinema-movie.movies.view',
            'show' => 'permission:cinema-movie.movies.view',
            'store' => 'permission:cinema-movie.movies.manage',
            'update' => 'permission:cinema-movie.movies.manage',
            'destroy' => 'permission:cinema-movie.movies.delete',
        ]);
    },
);
