<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Extensions\Gallery\Http\Controllers\Api\GalleryController;

/**
 * Gallery API routes.
 */
Route::get(
    '/gallery',
    [GalleryController::class, 'index'],
);

Route::post(
    '/gallery/upload',
    [GalleryController::class, 'store'],
);

Route::get(
    '/gallery/{photo}',
    [GalleryController::class, 'show'],
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
