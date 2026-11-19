<?php

/** CinemaMovie JSON:API resources. */

declare(strict_types=1);

use App\Extensions\CinemaMovie\Http\Controllers\Api\CinemaMovieController;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

JsonApiRoute::server('v1')->middleware('surface:api', 'auth:sanctum')->resources(
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
