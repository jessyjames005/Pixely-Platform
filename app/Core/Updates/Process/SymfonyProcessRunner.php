<?php

declare(strict_types=1);

namespace App\Core\Updates\Process;

use App\Core\Updates\Contracts\ProcessRunnerInterface;
use App\Core\Updates\DTO\DependencyUpdateResult;
use Symfony\Component\Process\Process;

/**
 * Runs package managers using Symfony Process argument arrays.
 * No shell is involved, preventing command-string injection.
 */
final class SymfonyProcessRunner implements ProcessRunnerInterface
{
    public function run(string $binary, array $arguments, string $workingDirectory, string $manager, string $operation): DependencyUpdateResult
    {
        $process = new Process([$binary, ...$arguments], $workingDirectory);
        $process->setTimeout(3600);
        $startedAt = microtime(true);
        $process->run();

        return new DependencyUpdateResult(
            manager: $manager,
            operation: $operation,
            successful: $process->isSuccessful(),
            exitCode: $process->getExitCode() ?? 1,
            stdout: trim($process->getOutput()),
            stderr: trim($process->getErrorOutput()),
            durationSeconds: round(microtime(true) - $startedAt, 3),
        );
    }
}
