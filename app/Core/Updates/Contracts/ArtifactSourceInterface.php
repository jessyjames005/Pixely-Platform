<?php

declare(strict_types=1);

namespace App\Core\Updates\Contracts;

use App\Core\Updates\DTO\ArtifactReference;

/**
 * Downloads artifacts from an approved hosting provider into a controlled staging path.
 */
interface ArtifactSourceInterface
{
    /**
     * Download an artifact and return the absolute path to the staged file.
     *
     * Implementations must enforce HTTPS, host allow-lists, size limits, timeouts,
     * redirect validation and safe destination handling.
     */
    public function download(ArtifactReference $artifact, string $stagingDirectory): string;
}
