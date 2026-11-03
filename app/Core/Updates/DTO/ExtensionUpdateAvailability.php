<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

/**
 * Registry discovery result for one installed extension.
 */
final readonly class ExtensionUpdateAvailability
{
    public function __construct(
        public InstalledExtension $installed,
        public ?UpdateCandidate $candidate,
        public ?string $reason = null,
    ) {
        if ($candidate !== null && $candidate->packageId !== $installed->packageId) {
            throw new \InvalidArgumentException('The update candidate package id does not match the installed extension.');
        }
    }

    public function hasUpdate(): bool
    {
        return $this->candidate !== null;
    }
}
