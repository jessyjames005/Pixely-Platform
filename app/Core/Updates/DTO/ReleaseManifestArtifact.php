<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

use App\Core\Updates\Enums\ArtifactProvider;

/** Artifact delivery and integrity metadata covered by the manifest signature. */
final readonly class ReleaseManifestArtifact
{
    public function __construct(
        public ArtifactProvider $provider,
        public string $uri,
        public string $filename,
        public int $size,
        public string $sha256,
        public ReleaseManifestSignature $signature,
    ) {
        ReleaseManifestValue::assertHttpsUrl($this->uri, 'artifact.uri');

        if (
            trim($this->filename) === ''
            || basename($this->filename) !== $this->filename
            || str_contains($this->filename, '\\')
            || str_contains($this->filename, '/')
        ) {
            throw new \InvalidArgumentException('artifact.filename must be a basename without path separators.');
        }

        if ($this->size < 1) {
            throw new \InvalidArgumentException('artifact.size must be a positive byte count.');
        }

        if (preg_match('/^[a-f0-9]{64}$/', $this->sha256) !== 1) {
            throw new \InvalidArgumentException('artifact.sha256 must contain 64 lowercase hexadecimal characters.');
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        ReleaseManifestValue::assertKeys(
            $data,
            ['provider', 'uri', 'filename', 'size', 'sha256', 'signature'],
            'artifact',
        );

        $provider = ArtifactProvider::tryFrom(ReleaseManifestValue::requiredString($data, 'provider', 'artifact'));
        if ($provider === null) {
            throw new \InvalidArgumentException('artifact.provider is not supported.');
        }

        $size = $data['size'] ?? null;
        if (!is_int($size)) {
            throw new \InvalidArgumentException('artifact.size must be an integer.');
        }

        $signature = $data['signature'] ?? null;
        if (!is_array($signature)) {
            throw new \InvalidArgumentException('artifact.signature must be an object.');
        }

        return new self(
            provider: $provider,
            uri: ReleaseManifestValue::requiredString($data, 'uri', 'artifact'),
            filename: ReleaseManifestValue::requiredString($data, 'filename', 'artifact'),
            size: $size,
            sha256: ReleaseManifestValue::requiredString($data, 'sha256', 'artifact'),
            signature: ReleaseManifestSignature::fromArray($signature, 'artifact.signature'),
        );
    }
}
