<?php

declare(strict_types=1);

namespace App\Core\Extensions\Versioning;

/**
 * Optional contract for extensions that declare versioned upgrade
 * steps, applied incrementally between the installed version and
 * the target version — instead of the update being an opaque full
 * file replacement with no logical version history.
 */
interface ExtensionUpgradableInterface
{
    /**
     * @return ExtensionUpgradeStepInterface[] not required to be pre-sorted
     */
    public function upgradeSteps(): array;
}
