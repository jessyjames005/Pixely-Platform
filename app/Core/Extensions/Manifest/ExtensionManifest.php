<?php

declare(strict_types=1);

namespace App\Core\Extensions\Manifest;

/**
 * Describes an extension metadata.
 */
final readonly class ExtensionManifest
{
    /**
     * @param string $name Unique extension identifier.
     * @param string $version Extension version.
     * @param string $class description.
     */
     public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $class,
    ) {}
}
