<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Extensions\Gallery\Controllers\GalleryController;

Route::get('/gallery', [GalleryController::class, 'index']);
