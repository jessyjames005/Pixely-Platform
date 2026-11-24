<?php

declare(strict_types=1);

use App\Extensions\Music\Http\Controllers\Api\MusicController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use Illuminate\Support\Facades\Route;

/**
 * Music extension API routes.
 *
 * Registered centrally by the Pixely Extension SDK v2 route registrar.
 * Honours the strict JSON:API contract (Accept / Content-Type
 * application/vnd.api+json) via EnsureJsonApiMediaType.
 */
Route::middleware([
    EnsureJsonApiMediaType::class,
    'auth:sanctum',
    'permission:music.items.view',
])->group(function () {
    Route::get('/music', [MusicController::class, 'index']);
});
