<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

/**
 * Reports the outcome of installing a previously verified update artifact.
 */
final readonly class InstallationResult
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $packageId,
        public string $installedVersion,
        public bool $successful,
        public array $metadata = [],
    ) {
    }
}
