<?php

declare(strict_types=1);

namespace App\Core\Extensions\Manifest;

/**
 * Describes an extension metadata.
 */
final readonly class ExtensionManifest
{
    /**
     * Create a new extension manifest.
     *
     * @param string[] $dependencies Extension identifiers required before loading.
     */
    public function __construct(
        /**
         * Unique extension identifier.
         */
        public string $id,

        /**
         * Extension display name.
         */
        public string $name,

        /**
         * Extension version.
         */
        public string $version,

        /**
         * Extension entrypoint class.
         */
        public string $class,

        /**
         * Absolute extension directory.
         */
        public string $path,

        /**
         * Required extension identifiers.
         *
         * @var string[]
         */
        public array $dependencies = [],
    ) {
    }
}
