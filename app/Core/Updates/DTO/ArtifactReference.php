<?php

declare(strict_types=1);

namespace App\Core\Updates\DTO;

use App\Core\Updates\Enums\ArtifactProvider;

/**
 * Immutable reference to an update archive and its integrity metadata.
 */
final readonly class ArtifactReference
{
    public function __construct(
        public ArtifactProvider $provider,
        public string $uri,
        public string $sha256,
        public ?string $signature = null,
        public ?string $keyId = null,
    ) {
        $scheme = parse_url($this->uri, PHP_URL_SCHEME);

        if (filter_var($this->uri, FILTER_VALIDATE_URL) === false || strtolower((string) $scheme) !== 'https') {
            throw new \InvalidArgumentException('The artifact URI must be a valid HTTPS URL.');
        }

        if (preg_match('/^[a-f0-9]{64}$/i', $this->sha256) !== 1) {
            throw new \InvalidArgumentException('The artifact SHA-256 checksum must contain 64 hexadecimal characters.');
        }

        if (($this->signature === null) !== ($this->keyId === null)) {
            throw new \InvalidArgumentException('Artifact signature and key identifier must be provided together.');
        }
    }
}
