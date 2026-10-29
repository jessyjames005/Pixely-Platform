<?php

declare(strict_types=1);

namespace App\Core\Updates\Contracts;

use App\Core\Updates\DTO\DependencyUpdateResult;

/** Common boundary for Composer and npm dependency update adapters. */
interface DependencyUpdaterInterface
{
    public function check(): DependencyUpdateResult;

    public function update(bool $dryRun = false, bool $securityOnly = false): DependencyUpdateResult;

    public function audit(): DependencyUpdateResult;
}
