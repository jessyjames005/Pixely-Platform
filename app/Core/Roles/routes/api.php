<?php

/**
 * Core role and permission management API routes.
 *
 * Registered under api/v1 by RoleServiceProvider, following the
 * same per-module routing convention as extensions and other
 * Core modules (Auth, Users).
 */

declare(strict_types=1);

use App\Core\Roles\Http\Controllers\PermissionController;
use App\Core\Roles\Http\Controllers\RoleController;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\Relationships;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

JsonApiRoute::server('v1')->resources(function (ResourceRegistrar $server): void {
    $server->resource('roles', RoleController::class)
        ->middleware('auth:sanctum')
        ->relationships(function (Relationships $relationships): void {
            $relationships->hasMany('permissions')->only('show', 'update');
        });

    $server->resource('permissions', PermissionController::class)
        ->middleware('auth:sanctum');
});
