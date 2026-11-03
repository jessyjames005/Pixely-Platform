<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\Contracts\DependencyUpdaterInterface;
use App\Core\Updates\Contracts\ProcessRunnerInterface;
use App\Core\Updates\DTO\DependencyUpdateResult;
use RuntimeException;

/** Shared process execution and result validation for package-manager adapters. */
abstract class AbstractDependencyUpdater implements DependencyUpdaterInterface
{
    public function __construct(
        protected readonly ProcessRunnerInterface $processRunner,
        protected readonly string $workingDirectory,
        protected readonly string $binary,
    ) {
    }

    /** @param list<string> $arguments */
    protected function execute(array $arguments, string $operation): DependencyUpdateResult
    {
        $result = $this->processRunner->run(
            $this->binary,
            $arguments,
            $this->workingDirectory,
            $this->managerName(),
            $operation,
        );

        if (! $result->successful) {
            $details = $result->stderr !== '' ? $result->stderr : $result->stdout;
            throw new RuntimeException(sprintf(
                '%s %s failed with exit code %d: %s',
                ucfirst($this->managerName()),
                $operation,
                $result->exitCode,
                $details,
            ));
        }

        return $result;
    }

    abstract protected function managerName(): string;
}
