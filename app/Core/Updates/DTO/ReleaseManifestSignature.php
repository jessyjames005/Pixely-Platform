<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

/** Immutable Ed25519 signature envelope used by a release manifest or artifact. */
final readonly class ReleaseManifestSignature
{
    public const ALGORITHM = 'Ed25519';

    public function __construct(
        public string $algorithm,
        public string $keyId,
        public string $value,
    ) {
        if ($this->algorithm !== self::ALGORITHM) {
            throw new \InvalidArgumentException('The signature algorithm must be Ed25519.');
        }

        if (trim($this->keyId) === '') {
            throw new \InvalidArgumentException('The signature key identifier cannot be empty.');
        }

        $decoded = base64_decode($this->value, true);
        if (!is_string($decoded) || base64_encode($decoded) !== $this->value || strlen($decoded) !== 64) {
            throw new \InvalidArgumentException('The Ed25519 signature must be canonical Base64 encoding of 64 bytes.');
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $context = 'signature'): self
    {
        ReleaseManifestValue::assertKeys($data, ['algorithm', 'key_id', 'value'], $context);

        return new self(
            algorithm: ReleaseManifestValue::requiredString($data, 'algorithm', $context),
            keyId: ReleaseManifestValue::requiredString($data, 'key_id', $context),
            value: ReleaseManifestValue::requiredString($data, 'value', $context),
        );
    }
}
