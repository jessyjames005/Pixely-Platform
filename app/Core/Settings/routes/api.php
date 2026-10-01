<?php

/**
 * Core settings and localization API routes.
 *
 * Platform settings require settings.platform.view/manage — they
 * affect every user of the platform. User settings (own locale
 * preference) are self-service: any authenticated user may read
 * and update their own, no dedicated permission required.
 */

declare(strict_types=1);

use App\Core\Settings\Http\Controllers\PlatformSettingController;
use App\Core\Settings\Http\Controllers\UserSettingController;
use App\Core\Settings\Http\Controllers\LocaleController;
use App\JsonApi\V1\Middleware\EnsureJsonApiMediaType;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

JsonApiRoute::server('v1')
    ->middleware(EnsureJsonApiMediaType::class)
    ->resources(function (ResourceRegistrar $server): void {
        $server->resource('locales', LocaleController::class)->only('index');

        $server->resource('platform-settings', PlatformSettingController::class)
        ->only('show', 'update')
        ->middleware([
            '*' => ['auth:sanctum'],
            'show' => ['permission:settings.platform.view'],
            'update' => ['permission:settings.platform.manage'],
        ]);

        $server->resource('user-settings', UserSettingController::class)
        ->only('show', 'update')
        ->middleware(['auth:sanctum']);
    });
