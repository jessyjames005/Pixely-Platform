<?php

declare(strict_types=1);

namespace App\Core\Extensions\Discovery;

use App\Core\Extensions\Manifest\ExtensionManifest;

/**
 * Reads PHP-based extension manifest files.
 */
final class ExtensionManifestReader
{
    /**
     * Read extension manifest file.
     */
    public function read(string $manifestPath): ?array
    {
        if (!is_file($manifestPath)) {
            return null;
        }

        $data = require $manifestPath;

        return is_array($data) ? $data : null;
    }

    /**
     * Create an ExtensionManifest from manifest data.
     *
     * @param array<string, mixed> $data
     */
    public function createManifest(array $data): ?ExtensionManifest
    {
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? null;
        $version = $data['version'] ?? null;
        $class = $data['class'] ?? null;
        $dependencies = $data['requires'] ?? [];

        if (
            !is_string($id)
            || !is_string($name)
            || !is_string($version)
            || !is_string($class)
            || !is_array($dependencies)
        ) {
            return null;
        }

        $dependencies = array_values(
            array_filter(
                $dependencies,
                static fn(mixed $dependency): bool =>
                is_string($dependency),
            ),
        );

        return new ExtensionManifest(
            id: $id,
            name: $name,
            version: $version,
            class: $class,
            dependencies: $dependencies,
        );
    }
}
