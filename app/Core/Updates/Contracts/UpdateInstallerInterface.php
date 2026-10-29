<?php

declare(strict_types=1);

namespace App\Core\Updates\Contracts;

use App\Core\Updates\DTO\InstallationResult;
use App\Core\Updates\DTO\UpdatePlanItem;

/**
 * Installs an update that has already been downloaded and verified.
 */
interface UpdateInstallerInterface
{
    /**
     * Install a verified artifact using the existing extension lifecycle where applicable.
     *
     * @throws \Throwable
     */
    public function install(UpdatePlanItem $item, string $verifiedArtifactPath): InstallationResult;
}
