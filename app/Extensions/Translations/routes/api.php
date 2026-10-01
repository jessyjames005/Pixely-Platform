<?php

declare(strict_types=1);

use App\Extensions\Translations\Http\Controllers\TranslationController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

JsonApiRoute::server('v1')
    ->middleware(EnsureJsonApiMediaType::class)
    ->resources(function (ResourceRegistrar $server): void {
        $server->resource('translation-modules', TranslationController::class)
        ->only('index')
        ->middleware([
            '*' => ['auth:sanctum'],
            'index' => ['permission:translations.strings.view'],
        ]);

        $server->resource('translation-groups', TranslationController::class)
        ->only('index', 'update')
        ->middleware([
            '*' => ['auth:sanctum'],
            'index' => ['permission:translations.strings.view'],
            'update' => ['permission:translations.strings.manage'],
        ]);

        $server->resource('translation-strings', TranslationController::class)
        ->only('index')
        ->middleware([
            '*' => ['auth:sanctum'],
            'index' => ['permission:translations.strings.view'],
        ]);
    });
