<?php

declare(strict_types=1);

use App\Extensions\Gallery\Http\Controllers\Api\GalleryController;
use Illuminate\Support\Facades\Route;

/**
 * Gallery API routes.
 */

Route::get(
    '/gallery',
    [GalleryController::class, 'index'],
);

Route::get(
    '/gallery/{photo}',
    [GalleryController::class, 'show'],
);

Route::middleware('auth:sanctum')->group(function () {
    Route::post(
        '/gallery/upload',
        [GalleryController::class, 'store'],
    );

    Route::put(
        '/gallery/{photo}',
        [GalleryController::class, 'update'],
    );

    /**
     * Delete a gallery photo.
     */
    Route::delete(
        '/gallery/{photo}',
        [GalleryController::class, 'destroy'],
    );
});
