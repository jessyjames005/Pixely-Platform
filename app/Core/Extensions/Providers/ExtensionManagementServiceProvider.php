<?php

declare(strict_types=1);

namespace App\Core\Extensions\Providers;

use App\Core\Extensions\Installer\ExtensionInstaller;
use App\Core\Extensions\Installer\ExtensionInstallerInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Core extension management API routes.
 */
final class ExtensionManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExtensionInstallerInterface::class, ExtensionInstaller::class);
    }

    public function boot(): void
    {
        $this->app->router
            ->middleware('api')
            ->prefix('api/v1')
            ->group(
                __DIR__ . '/../routes/api.php'
            );
    }
}
