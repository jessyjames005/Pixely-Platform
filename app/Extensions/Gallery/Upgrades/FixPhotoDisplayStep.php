<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Upgrades;

use App\Core\Extensions\Versioning\ExtensionUpgradeStepInterface;

/**
 * Example non-schema upgrade step: a display/bugfix release.
 * Nothing to migrate — this exists to demonstrate that a version
 * step doesn't have to touch the database at all.
 */
final class FixPhotoDisplayStep implements ExtensionUpgradeStepInterface
{
    public function version(): string
    {
        return '1.0.1';
    }

    public function description(): string
    {
        return 'Fix photo display rendering issue (no schema change).';
    }

    public function apply(): void
    {
        // No-op: this release only changed frontend rendering code,
        // already deployed with the package files themselves.
    }
}
