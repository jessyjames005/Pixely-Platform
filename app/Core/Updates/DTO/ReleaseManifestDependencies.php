<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

/** Required and conflicting package constraints included in the signed manifest. */
final readonly class ReleaseManifestDependencies
{
    /**
     * @param array<string, string> $requires
     * @param array<string, string> $conflicts
     */
    public function __construct(public array $requires, public array $conflicts)
    {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        ReleaseManifestValue::assertKeys($data, ['requires', 'conflicts'], 'dependencies');

        if (!array_key_exists('requires', $data) || !array_key_exists('conflicts', $data)) {
            throw new \InvalidArgumentException('dependencies.requires and dependencies.conflicts are required.');
        }

        return new self(
            requires: ReleaseManifestValue::stringMap($data['requires'], 'dependencies.requires', true),
            conflicts: ReleaseManifestValue::stringMap($data['conflicts'], 'dependencies.conflicts', true),
        );
    }
}
