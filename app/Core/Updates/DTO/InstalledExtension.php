<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

/**
 * Immutable snapshot of an extension currently installed in Pixely.
 */
final readonly class InstalledExtension
{
    /**
     * @param list<string> $dependencies
     * @param list<string> $surfaces
     */
    public function __construct(
        public string $id,
        public string $packageId,
        public string $name,
        public string $version,
        public string $minimumKernelVersion,
        public string $path,
        public array $dependencies = [],
        public array $surfaces = [],
    ) {
        if (trim($this->id) === '' || trim($this->packageId) === '') {
            throw new \InvalidArgumentException('An installed extension must have an id and package id.');
        }

        if (preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $this->version) !== 1) {
            throw new \InvalidArgumentException('The installed extension version must follow semantic versioning.');
        }
    }
}
