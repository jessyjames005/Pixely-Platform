<?php

declare(strict_types=1);

namespace App\Core\Extensions\Discovery;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Dependency\ExtensionDependencyResolver;
use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Loads and instantiates all discovered extensions.
 */
final class ExtensionRepository
{
    public function __construct(
        private readonly ExtensionDiscoverer $discoverer,
        private readonly ExtensionManifestReader $reader,
        private readonly ExtensionDependencyResolver $dependencyResolver,
    ) {}

    /**
     * Return all discovered extension manifests in dependency order.
     *
     * @return ExtensionManifest[]
     */
    public function manifests(string $basePath): array
    {
        $manifests = [];

        foreach ($this->discoverer->discover($basePath) as $path) {
            $manifestData = $this->reader->read(
                $path . '/extension.php',
            );

            if ($manifestData === null) {
                continue;
            }

            $manifest = $this->reader->createManifest(
                $manifestData,
                $path,
            );

            if ($manifest === null) {
                continue;
            }

            $manifests[] = $manifest;
        }

        return $this->dependencyResolver->resolve($manifests);
    }

    /**
     * Return all discovered extensions.
     *
     * @return ExtensionInterface[]
     */
    public function all(string $basePath): array
    {
        $extensions = [];

        foreach ($this->manifests($basePath) as $manifest) {
            $class = $manifest->class;

            if (
                ! class_exists($class) ||
                ! is_subclass_of($class, ExtensionInterface::class)
            ) {
                continue;
            }

            /** @var ExtensionInterface $extension */
            $extensions[] = new $class();
        }

        return $extensions;
    }
}
