<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

use App\Core\Updates\Enums\UpdateTargetType;

/** Runtime version constraints declared by a release publisher. */
final readonly class ReleaseManifestCompatibility
{
    /** @param array<string, string> $constraints */
    public function __construct(public array $constraints)
    {
        foreach ($this->constraints as $runtime => $constraint) {
            if (!is_string($runtime) || preg_match('/^[a-z][a-z0-9._-]*$/', $runtime) !== 1) {
                throw new \InvalidArgumentException('compatibility keys must be lowercase runtime identifiers.');
            }
            if (!is_string($constraint) || trim($constraint) === '') {
                throw new \InvalidArgumentException('compatibility constraints must be non-empty strings.');
            }
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, UpdateTargetType $type): self
    {
        $constraints = ReleaseManifestValue::stringMap($data, 'compatibility');

        $requiredRuntime = $type === UpdateTargetType::Platform ? 'php' : 'pixely';
        if (!array_key_exists($requiredRuntime, $constraints)) {
            throw new \InvalidArgumentException(sprintf('compatibility.%s is required for this package type.', $requiredRuntime));
        }

        return new self($constraints);
    }
}
