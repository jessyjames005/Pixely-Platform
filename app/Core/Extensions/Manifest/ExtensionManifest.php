<?php

declare(strict_types=1);

namespace App\Core\Extensions\Manifest;

/**
 * Describes an extension metadata.
 */
final readonly class ExtensionManifest
{
    /**
     * @param string $id Unique extension identifier.
     * @param string $name Extension display name.
     * @param string $version Extension version.
     * @param string $class Extension entrypoint class.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $version,
        public readonly string $class,
    ) {}
}
