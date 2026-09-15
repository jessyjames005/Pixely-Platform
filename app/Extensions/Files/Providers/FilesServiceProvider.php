<?php

declare(strict_types=1);

namespace App\Extensions\Files\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers Files extension services, routes and migrations.
 *
 * FileUploadService/FileUploadValidator remain the shared entry point
 * other extensions use for validated upload/thumbnail handling — the
 * routes registered here are for the extension's OWN standalone API
 * (list/upload/delete against the central `files` table), independent
 * of that.
 */
final class FilesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->router
            ->middleware('api')
            ->prefix('api/v1')
            ->group(
                __DIR__ . '/../routes/api.php'
            );

        $this->loadMigrationsFrom(
            __DIR__ . '/../Database/Migrations'
        );
    }
}
