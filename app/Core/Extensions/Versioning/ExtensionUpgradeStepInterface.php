<?php

declare(strict_types=1);

namespace App\Core\Extensions\Versioning;

/**
 * A single, versioned upgrade step for an extension.
 *
 * A step may or may not touch the database schema — it can be a
 * pure bugfix/data-correction step just as well as a migration.
 */
interface ExtensionUpgradeStepInterface
{
    /**
     * The extension version this step upgrades TO.
     */
    public function version(): string;

    /**
     * Human-readable description, shown in the audit log and admin UI.
     */
    public function description(): string;

    /**
     * Apply the step. Called at most once per version, in order.
     */
    public function apply(): void;
}
