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
 */
Route::prefix('public')->group(function (): void {
    Route::get('/movies/{slug}', [PublicMovieController::class, 'show']);
    Route::get('/trending-movies', [PublicMovieController::class, 'trending']);
    Route::get('/tmdb/popular-movies', [PublicMovieController::class, 'popularMovies']);
    Route::get('/tmdb/top-rated-movies', [PublicMovieController::class, 'topRatedMovies']);
    Route::get('/tmdb/upcoming-movies', [PublicMovieController::class, 'upcomingMovies']);
    Route::get('/tmdb/now-playing-movies', [PublicMovieController::class, 'nowPlayingMovies']);
    Route::get('/tmdb/popular-people', [PublicMovieController::class, 'popularPeople']);
    Route::get('/tmdb/person/{id}', [PublicMovieController::class, 'personDetails']);
    Route::get('/tmdb/person/{id}/movie-credits', [PublicMovieController::class, 'personMovieCredits']);
    Route::get('/tmdb/movie/{id}', [PublicMovieController::class, 'movieDetails']);
    Route::get('/tmdb/movie/{id}/credits', [PublicMovieController::class, 'movieCredits']);
    Route::get('/tmdb/search', [PublicMovieController::class, 'search']);
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
