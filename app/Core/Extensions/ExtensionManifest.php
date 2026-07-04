<?php

declare(strict_types=1);

namespace App\Core\Extensions;

/**
 * Describes an extension metadata.
 */
final readonly class ExtensionManifest
{
    /**
     * @param string $name Unique extension identifier.
     * @param string $version Extension version.
     * @param string $description Short description.
     * @param string $author Extension author.
     */
    public function __construct(
        public string $name,
        public string $version,
        public string $description,
        public string $author,
    ) {
    }
}
