<?php

declare(strict_types=1);

namespace App\Core\Updates\Contracts;

use App\Core\Updates\DTO\DependencyUpdateResult;

/** Executes a fixed package-manager command without invoking a shell. */
interface ProcessRunnerInterface
{
    /**
     * @param list<string> $arguments
     */
    public function run(string $binary, array $arguments, string $workingDirectory, string $manager, string $operation): DependencyUpdateResult;
}
