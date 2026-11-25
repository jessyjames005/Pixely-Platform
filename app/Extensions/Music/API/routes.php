<?php

/**
 * Music extension API routes.
 *
 * Exposes public read-only catalog proxy routes under /api/v1/public/music/...
 * and JSON:API administration routes under /api/v1/... (auth:sanctum + permissions).
 */

declare(strict_types=1);

use App\Extensions\Music\Http\Controllers\Api\MusicController;
use App\Extensions\Music\Http\Controllers\PublicMusicController;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/**
 * Public read-only endpoints with throttling and ID constraints.
 */
Route::prefix('public/music')->middleware('throttle:120,1')->group(function (): void {
    Route::get('/tracks', [PublicMusicController::class, 'tracks']);
    Route::get('/tracks/{id}', [PublicMusicController::class, 'trackDetails'])->whereNumber('id');
    Route::get('/albums', [PublicMusicController::class, 'albums']);
    Route::get('/albums/{id}', [PublicMusicController::class, 'albumDetails'])->whereNumber('id');
    Route::get('/artists', [PublicMusicController::class, 'artists']);
    Route::get('/artists/{id}', [PublicMusicController::class, 'artistDetails'])->whereNumber('id');
    Route::get('/genres', [PublicMusicController::class, 'genres']);
});

/**
 * JSON:API administration routes.
 */
JsonApiRoute::server('v1')->middleware('auth:sanctum')->resources(
    function (ResourceRegistrar $server): void {
        $server->resource('music-tracks', MusicController::class)->middleware([
            'index' => 'permission:music.tracks.view',
            'show' => 'permission:music.tracks.view',
            'store' => 'permission:music.tracks.manage',
            'update' => 'permission:music.tracks.manage',
            'destroy' => 'permission:music.tracks.delete',
        ]);
        $server->resource('music-albums', MusicController::class)->middleware([
            'index' => 'permission:music.albums.view',
            'show' => 'permission:music.albums.view',
            'store' => 'permission:music.albums.manage',
            'update' => 'permission:music.albums.manage',
            'destroy' => 'permission:music.albums.delete',
        ]);
        $server->resource('music-artists', MusicController::class)->middleware([
            'index' => 'permission:music.artists.view',
            'show' => 'permission:music.artists.view',
            'store' => 'permission:music.artists.manage',
            'update' => 'permission:music.artists.manage',
            'destroy' => 'permission:music.artists.delete',
        ]);
        $server->resource('music-genres', MusicController::class)->middleware([
            'index' => 'permission:music.genres.view',
            'show' => 'permission:music.genres.view',
            'store' => 'permission:music.genres.manage',
            'update' => 'permission:music.genres.manage',
            'destroy' => 'permission:music.genres.delete',
        ]);
    },
);
