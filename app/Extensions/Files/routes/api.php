<?php

declare(strict_types=1);

use App\Extensions\Files\Http\Controllers\Api\FileController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use LaravelJsonApi\Laravel\Routing\Route as JsonApiRoutingRoute;

// Standalone Files API routes. Unlike Gallery (public read), these routes
// remain behind auth:sanctum because the Files API is an admin file manager.

JsonApiRoute::server('v1')
    ->middleware(EnsureJsonApiMediaType::class)
    ->resources(function (ResourceRegistrar $server): void {
        $server->resource('files', FileController::class)
            ->only('index', 'show', 'destroy')
            ->middleware('auth:sanctum');
    });

Route::post('/files', [FileController::class, 'upload'])
    ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'files')
    ->middleware(['jsonapi:v1', EnsureJsonApiMediaType::class, 'auth:sanctum']);
