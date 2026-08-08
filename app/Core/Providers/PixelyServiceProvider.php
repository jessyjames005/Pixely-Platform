<?php

declare(strict_types=1);

namespace App\Core\Providers;

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Discovery\ExtensionDiscoverer;
use App\Core\Extensions\Discovery\ExtensionManifestReader;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Repositories\JsonExtensionStateRepository;
use App\Core\Extensions\Registry\ExtensionRegistry;
use App\Core\Kernel\Kernel;
use Illuminate\Support\ServiceProvider;
use App\Core\Extensions\Dependency\ExtensionDependencyResolver;

final class PixelyServiceProvider extends ServiceProvider
{
    /**
     * Register services into the Laravel container.
     */
    public function register(): void
    {
        $this->app->singleton(
            ExtensionRegistry::class,
        );

        $this->app->singleton(
            ExtensionManager::class,
        );

        $this->app->singleton(
            ExtensionDiscoverer::class,
        );

        $this->app->singleton(
            ExtensionManifestReader::class,
        );

        $this->app->singleton(
            ExtensionRepository::class,
        );

        $this->app->singleton(
            ExtensionDependencyResolver::class,
        );

        $this->app->singleton(
            ExtensionStateRepositoryInterface::class,
            function ($app) {
                return new JsonExtensionStateRepository(
                    storage_path('pixely/extensions.json'),
                );
            },
        );

        $this->app->singleton(
            Kernel::class,
            function ($app) {
                return new Kernel(
                    $app->make(ExtensionManager::class),
                    $app->make(ExtensionRepository::class),
                    app_path('Extensions'),
                );
            },
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->make(Kernel::class)->boot();
    }
}
