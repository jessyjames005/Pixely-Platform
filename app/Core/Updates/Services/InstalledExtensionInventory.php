<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Versioning\ExtensionVersionRepository;
use App\Core\Updates\Contracts\ExtensionPackageIdResolverInterface;
use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use App\Core\Updates\DTO\InstalledExtension;

/**
 * Builds a read-only update inventory from Pixely's existing extension discovery
 * and installed-version services. It does not load extension classes.
 */
final class InstalledExtensionInventory implements InstalledExtensionInventoryInterface
{
    public function __construct(
        private readonly ExtensionRepository $extensions,
        private readonly ExtensionVersionRepository $versions,
        private readonly ExtensionPackageIdResolverInterface $packageIds,
    ) {
    }

    public function all(): array
    {
        $items = [];

        foreach ($this->extensions->manifests(base_path('app/Extensions')) as $manifest) {
            $items[] = new InstalledExtension(
                id: $manifest->id,
                packageId: $this->packageIds->resolve($manifest->id),
                name: $manifest->name,
                version: $this->versions->find($manifest->id) ?? $manifest->version,
                minimumKernelVersion: $manifest->minimum_kernel_version,
                path: $manifest->path,
                dependencies: $manifest->dependencies,
                surfaces: $manifest->surfaces,
            );
        }

        return $items;
    }

    public function find(string $extensionId): ?InstalledExtension
    {
        foreach ($this->all() as $extension) {
            if ($extension->id === $extensionId) {
                return $extension;
            }
        }

        return null;
    }
}
