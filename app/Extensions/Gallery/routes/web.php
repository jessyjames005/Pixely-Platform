<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Extensions\Gallery\Http\Controllers\GalleryController;

/**
 * Gallery web routes.
 */
Route::get('/gallery', [GalleryController::class, 'index']);
