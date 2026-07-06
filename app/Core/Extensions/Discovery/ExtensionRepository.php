<?php

declare(strict_types=1);

namespace App\Core\Extensions\Discovery;

use App\Core\Extensions\Contracts\ExtensionInterface;

/**
 * Loads and instantiates all discovered extensions.
 */
final class ExtensionRepository
{
    public function __construct(
        private readonly ExtensionDiscoverer $discoverer,
        private readonly ExtensionManifestReader $reader,
    ) {
    }

    /**
     * Return all discovered extensions.
     *
     * @return ExtensionInterface[]
     */
    public function all(string $basePath): array
    {
        $extensions = [];

        foreach ($this->discoverer->discover($basePath) as $path) {

            $manifest = $this->reader->read($path . '/extension.php');

            if ($manifest === null) {
                continue;
            }

            $class = $manifest['class'] ?? null;

            if (
                $class === null ||
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
