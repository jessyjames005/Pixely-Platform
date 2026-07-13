<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Extensions\Gallery\Http\Controllers\GalleryController;

/**
 * Gallery web routes.
 */
Route::get('/gallery', [GalleryController::class, 'index']);
Route::post(
    '/gallery/upload',
    [GalleryController::class, 'store']
);
/**
 * Display a single photo.
 */
Route::get(
    '/gallery/{photo}',
    [GalleryController::class, 'show']
);
/**
 * Update a photo.
 */
Route::put(
    '/gallery/{photo}',
    [GalleryController::class, 'update']
);
/**
 * Delete a photo.
 */
Route::delete(
    '/gallery/{photo}',
    [GalleryController::class, 'destroy']
);
