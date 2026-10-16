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

                // Composer's package:discover boots this provider before migrations run.
                // A fresh SQLite install may not have created its database file yet, so
                // avoid querying SQLite until that file exists. In that case, keep using
                // the legacy JSON repository; the next application boot will select SQL.
                if (
                    ! $this->sqliteDatabaseFileIsMissing($app)
                    && Schema::hasTable('extension_states')
                ) {
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
     * Detect a not-yet-created SQLite file before Schema attempts to connect to it.
     *
     * In-memory SQLite databases are intentionally excluded because they are valid
     * connections even though they do not correspond to a filesystem path.
     */
    private function sqliteDatabaseFileIsMissing($app): bool
    {
        $connectionName = (string) config('database.default');
        $connection = config('database.connections.' . $connectionName, []);

        if (($connection['driver'] ?? null) !== 'sqlite') {
            return false;
        }

        $databasePath = $connection['database'] ?? null;

        if (! is_string($databasePath) || $databasePath === '' || $databasePath === ':memory:') {
            return false;
        }

        // Laravel projects commonly configure an absolute SQLite path. Resolve a
        // relative path against the application base path for consistent detection.
        if (
            ! str_starts_with($databasePath, DIRECTORY_SEPARATOR)
            && ! preg_match('~^[A-Za-z]:[\\\\/]~', $databasePath)
        ) {
            $databasePath = $app->basePath($databasePath);
        }

        return ! is_file($databasePath);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->make(Kernel::class)->boot();
    }
}
