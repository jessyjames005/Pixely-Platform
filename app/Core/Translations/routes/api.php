<?php

/**
 * Public: the frontend needs this to render translated text before
 * login too (the login screen itself needs "Sign in", "Email", etc.).
 */

declare(strict_types=1);

use App\Core\Translations\Http\Controllers\LocaleCatalogController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

JsonApiRoute::server('v1')
    ->middleware(EnsureJsonApiMediaType::class)
    ->resources(function (ResourceRegistrar $server): void {
        $server->resource('translation-catalogs', LocaleCatalogController::class)->only('show');
    });
