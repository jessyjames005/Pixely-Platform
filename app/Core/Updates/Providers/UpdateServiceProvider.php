<?php

declare(strict_types=1);

namespace App\Core\Updates\Providers;

use App\Core\Updates\Contracts\ExtensionPackageIdResolverInterface;
use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use App\Core\Updates\Contracts\UpdateRegistryInterface;
use App\Core\Updates\Services\ExtensionUpdateDiscovery;
use App\Core\Updates\Services\HttpUpdateRegistry;
use App\Core\Updates\Services\InstalledExtensionInventory;
use App\Core\Updates\Services\JsonObjectKeyUniquenessGuard;
use App\Core\Updates\Services\PixelyExtensionPackageIdResolver;
use App\Core\Updates\Services\ReleaseManifestCanonicalizer;
use App\Core\Updates\Services\RuntimeCompatibilityChecker;
use App\Core\Updates\Services\SodiumReleaseManifestVerifier;
use Illuminate\Support\ServiceProvider;

/** Registers update discovery and the authenticated official Registry adapter. */
final class UpdateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ExtensionPackageIdResolverInterface::class,
            PixelyExtensionPackageIdResolver::class,
        );

        $this->app->bind(
            InstalledExtensionInventoryInterface::class,
            InstalledExtensionInventory::class,
        );

        $this->app->singleton(RuntimeCompatibilityChecker::class);
        $this->app->bind(
            ExtensionUpdateDiscovery::class,
            static fn ($app) => new ExtensionUpdateDiscovery(
                $app->make(InstalledExtensionInventoryInterface::class),
                $app->make(UpdateRegistryInterface::class),
                $app->make(RuntimeCompatibilityChecker::class),
            ),
        );

        $this->app->singleton(JsonObjectKeyUniquenessGuard::class);
        $this->app->singleton(ReleaseManifestCanonicalizer::class);
        $this->app->singleton(
            SodiumReleaseManifestVerifier::class,
            static fn ($app) => new SodiumReleaseManifestVerifier(
                $app->make(ReleaseManifestCanonicalizer::class),
                (array) $app['config']->get('update-manager.signing_public_keys', []),
            ),
        );

        $this->app->singleton(
            HttpUpdateRegistry::class,
            static fn ($app) => new HttpUpdateRegistry(
                (string) $app['config']->get('update-manager.registry_base_url', 'https://registry.pixely.example'),
                (int) $app['config']->get('update-manager.registry_timeout_seconds', 10),
                (int) $app['config']->get('update-manager.registry_per_page', 20),
                $app->make(JsonObjectKeyUniquenessGuard::class),
                $app->make(ReleaseManifestCanonicalizer::class),
                $app->make(SodiumReleaseManifestVerifier::class),
            ),
        );

        $this->app->bind(UpdateRegistryInterface::class, HttpUpdateRegistry::class);
    }
}
