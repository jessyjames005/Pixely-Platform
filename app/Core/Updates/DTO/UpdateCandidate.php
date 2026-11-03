<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;

/**
 * A release returned by the official Pixely Registry.
 */
final readonly class UpdateCandidate
{
    /**
     * @param array<string, string> $requires Required package identifiers and version constraints.
     * @param array<string, string> $conflicts Conflicting package identifiers and version constraints.
     * @param array<string, string> $runtimeConstraints Runtime requirements such as PHP and Pixely versions.
     */
    public function __construct(
        public UpdateTargetType $type,
        public string $packageId,
        public string $name,
        public string $version,
        public UpdateChannel $channel,
        public ArtifactReference $artifact,
        public array $requires = [],
        public array $conflicts = [],
        public array $runtimeConstraints = [],
        public ?string $releaseNotes = null,
        public ?\DateTimeImmutable $publishedAt = null,
    ) {
        if (trim($this->packageId) === '' || trim($this->name) === '') {
            throw new \InvalidArgumentException('An update candidate must have a package identifier and a name.');
        }

        if (preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $this->version) !== 1) {
            throw new \InvalidArgumentException('The candidate version must follow semantic versioning.');
        }
    }
}
