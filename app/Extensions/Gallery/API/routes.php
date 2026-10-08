<?php

/**
 * Gallery API routes.
 */

declare(strict_types=1);

use App\Extensions\Gallery\Http\Controllers\Api\GalleryController;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

JsonApiRoute::server('v1')->middleware('surface:api')->resources(function (ResourceRegistrar $server): void {
    $server->resource('photos', GalleryController::class)->middleware([
        'store' => 'auth:sanctum',
        'update' => 'auth:sanctum',
        'destroy' => 'auth:sanctum',
    ]);
});

Route::post(
    '/photos/upload',
    [GalleryController::class, 'upload'],
)->middleware(['jsonapi:v1', 'auth:sanctum']);
