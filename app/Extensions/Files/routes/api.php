<?php

declare(strict_types=1);

use App\Extensions\Files\Http\Controllers\Api\FileController;
use Illuminate\Support\Facades\Route;

/**
 * Standalone Files API routes.
 *
 * Unlike Gallery (public read, public-facing content), every route here
 * sits behind auth:sanctum — this is a general admin file manager, and
 * listing every file ever uploaded through it isn't something to expose
 * publicly by default.
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/files', [FileController::class, 'index']);
    Route::post('/files', [FileController::class, 'store']);
    Route::get('/files/{file}', [FileController::class, 'show']);
    Route::delete('/files/{file}', [FileController::class, 'destroy']);
});
