<?php

declare(strict_types=1);

namespace App\Core\Updates\Providers;

use App\Core\Updates\Contracts\ExtensionPackageIdResolverInterface;
use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use App\Core\Updates\Services\InstalledExtensionInventory;
use App\Core\Updates\Services\PixelyExtensionPackageIdResolver;
use Illuminate\Support\ServiceProvider;

/**
 * Registers update discovery services without binding a concrete registry transport.
 *
 * The official registry adapter is supplied in a later delivery phase.
 */
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
    }
}
