<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\DTO\DependencyUpdateResult;
use LogicException;

/** Safe Composer adapter. Updates remain constrained by composer.json. */
final class ComposerDependencyUpdater extends AbstractDependencyUpdater
{
    public function check(): DependencyUpdateResult
    {
        return $this->execute(['outdated', '--direct', '--format=json', '--no-interaction'], 'check');
    }

    public function update(bool $dryRun = false, bool $securityOnly = false): DependencyUpdateResult
    {
        // Composer has no native security-only update command. Do not pretend
        // that an audit is an update or silently upgrade every dependency.
        if ($securityOnly) {
            throw new LogicException('Composer does not support a safe security-only update operation. Run an audited, reviewed Composer update instead.');
        }

        $arguments = ['update', '--with-all-dependencies', '--no-interaction'];

        if ($dryRun) {
            $arguments[] = '--dry-run';
        }

        return $this->execute($arguments, $dryRun ? 'update-dry-run' : 'update');
    }

    public function audit(): DependencyUpdateResult
    {
        return $this->processRunner->run(
            $this->binary,
            ['audit', '--locked', '--format=json'],
            $this->workingDirectory,
            $this->managerName(),
            'audit',
        );
    }

    protected function managerName(): string
    {
        return 'composer';
    }
}
