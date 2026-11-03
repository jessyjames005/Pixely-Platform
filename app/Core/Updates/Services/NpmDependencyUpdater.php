<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\DTO\DependencyUpdateResult;

/** Safe npm adapter. It never uses --force or changes declared semver ranges. */
final class NpmDependencyUpdater extends AbstractDependencyUpdater
{
    public function check(): DependencyUpdateResult
    {
        // npm outdated exits with code 1 when updates are available. The
        // process runner reports the raw result; this is an expected outcome.
        return $this->processRunner->run(
            $this->binary,
            ['outdated', '--json'],
            $this->workingDirectory,
            $this->managerName(),
            'check',
        );
    }

    public function update(bool $dryRun = false, bool $securityOnly = false): DependencyUpdateResult
    {
        $arguments = $securityOnly
            ? ['audit', 'fix', '--no-fund']
            : ['update', '--no-fund'];

        if ($dryRun) {
            $arguments[] = '--dry-run';
        }

        return $this->execute($arguments, $securityOnly ? 'security-update' : ($dryRun ? 'update-dry-run' : 'update'));
    }

    public function audit(): DependencyUpdateResult
    {
        return $this->processRunner->run(
            $this->binary,
            ['audit', '--json'],
            $this->workingDirectory,
            $this->managerName(),
            'audit',
        );
    }

    protected function managerName(): string
    {
        return 'npm';
    }
}
