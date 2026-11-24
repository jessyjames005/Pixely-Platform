<?php

/**
 * Music extension API routes.
 *
 * Registered centrally by the Pixely Extension SDK v2 route registrar.
 * Honours the strict JSON:API contract (Accept / Content-Type
 * application/vnd.api+json) via the `jsonapi:v1` middleware applied by the
 * JSON:API server, and gates every action with a per-resource permission.
 *
 * The rights gate (unknown rights can never be public) is enforced at the model
 * layer, so the write endpoints below cannot be used to publish unvetted tracks.
 */

declare(strict_types=1);

use App\Extensions\Music\Http\Controllers\Api\MusicController;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

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
