<?php

declare(strict_types=1);

namespace App\Core\Updates\Contracts;

use App\Core\Updates\DTO\UpdateCandidate;
use App\Core\Updates\DTO\UpdateQuery;

/**
 * Reads release metadata from the official Pixely Registry.
 *
 * Artifact hosting is deliberately separate and is handled by an artifact source.
 */
interface UpdateRegistryInterface
{
    /**
     * Find compatible releases newer than the installed version.
     *
     * @return iterable<UpdateCandidate>
     */
    public function findUpdates(UpdateQuery $query): iterable;

    /**
     * Retrieve an exact release from the official registry.
     */
    public function findRelease(string $packageId, string $version): ?UpdateCandidate;
}
