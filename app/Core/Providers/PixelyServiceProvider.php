<?php

declare(strict_types=1);

namespace App\Core\Providers;

use App\Core\Extensions\Capabilities\Registry\ExtensionCapabilityRegistry;
use App\Core\Extensions\Capabilities\Registry\ExtensionRouteRegistrar;
use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Dependency\ExtensionDependencyResolver;
use App\Core\Extensions\Discovery\ExtensionDiscoverer;
use App\Core\Extensions\Discovery\ExtensionManifestReader;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Registry\ExtensionRegistry;
use App\Core\Extensions\Migration\ExtensionStateJsonImporter;
use App\Core\Extensions\Repositories\DatabaseExtensionStateRepository;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use App\Core\Extensions\Repositories\JsonExtensionStateRepository;
use App\Core\Kernel\Kernel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
            ExtensionCapabilityRegistry::class,
        );

        $this->app->singleton(
            ExtensionRouteRegistrar::class,
        );

        $this->app->singleton(
            ExtensionStateRepositoryInterface::class,
            function ($app): ExtensionStateRepositoryInterface {
                // Keep extension state isolated during tests.
                // Production continues to use the persistent JSON repository.
                if ($app->environment('testing')) {
                    return new InMemoryExtensionStateRepository();
                }

                // Keep artisan migrate usable before the new table exists.
                // Once migrated, runtime state is stored in the database.
                if (Schema::hasTable('extension_states')) {
                    // Import legacy states before Kernel::boot() can initialize new rows as enabled.
                    $app->make(ExtensionStateJsonImporter::class)->import(
                        storage_path('pixely/extensions.json'),
                    );

                    return new DatabaseExtensionStateRepository();
                }

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
                    $app->make(ExtensionRouteRegistrar::class),
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
