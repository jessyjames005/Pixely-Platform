<?php

declare(strict_types=1);

namespace App\Core\Updates\Contracts;

use App\Core\Updates\DTO\ArtifactReference;

/**
 * Verifies artifact integrity and publisher authenticity before installation.
 */
interface UpdateVerifierInterface
{
    /**
     * Verify the SHA-256 checksum and the registry-approved signature.
     *
     * @throws \App\Core\Updates\Exceptions\UpdateVerificationException
     */
    public function verify(string $artifactPath, ArtifactReference $artifact): void;
}
