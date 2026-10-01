<?php

declare(strict_types=1);

use App\Core\Auth\Http\Controllers\AuthController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;
use LaravelJsonApi\Laravel\Routing\Route as JsonApiRoutingRoute;

JsonApiRoute::server('v1')
    ->middleware(EnsureJsonApiMediaType::class)
    ->resources(function (ResourceRegistrar $server): void {
        Route::middleware('web')
            ->post('/auth/login', [AuthController::class, 'login'])
            ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');

        Route::middleware(['web', 'auth:sanctum'])->group(function (): void {
            Route::post('/auth/logout', [AuthController::class, 'logout'])
                ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
            Route::get('/auth/me', [AuthController::class, 'me'])
                ->defaults(JsonApiRoutingRoute::RESOURCE_TYPE, 'users');
        });
    });
