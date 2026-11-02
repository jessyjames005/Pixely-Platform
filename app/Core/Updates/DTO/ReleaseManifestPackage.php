<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

use App\Core\Updates\Enums\UpdateTargetType;

/** Canonical identity of the package described by a release manifest. */
final readonly class ReleaseManifestPackage
{
    public function __construct(
        public UpdateTargetType $type,
        public string $id,
        public string $name,
    ) {
        ReleaseManifestValue::assertPackageId($this->id, 'package.id');

        if (trim($this->name) === '') {
            throw new \InvalidArgumentException('package.name cannot be empty.');
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        ReleaseManifestValue::assertKeys($data, ['type', 'id', 'name'], 'package');
        $type = UpdateTargetType::tryFrom(ReleaseManifestValue::requiredString($data, 'type', 'package'));

        if ($type === null) {
            throw new \InvalidArgumentException('package.type is not supported.');
        }

        return new self(
            type: $type,
            id: ReleaseManifestValue::requiredString($data, 'id', 'package'),
            name: ReleaseManifestValue::requiredString($data, 'name', 'package'),
        );
    }
}
