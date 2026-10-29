<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\Contracts\ProcessRunnerInterface;
use App\Core\Updates\DTO\DependencyUpdateResult;
use App\Core\Updates\Services\ComposerDependencyUpdater;
use App\Core\Updates\Services\NpmDependencyUpdater;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DependencyUpdaterTest extends TestCase
{
    public function test_composer_update_uses_lockfile_compatible_arguments_and_supports_dry_run(): void
    {
        $runner = new RecordingProcessRunner();
        $updater = new ComposerDependencyUpdater($runner, '/project', 'composer');

        $updater->update(dryRun: true);

        self::assertSame(['update', '--with-all-dependencies', '--no-interaction', '--dry-run'], $runner->calls[0]['arguments']);
        self::assertSame('/project', $runner->calls[0]['workingDirectory']);
    }

    public function test_npm_security_update_never_uses_force(): void
    {
        $runner = new RecordingProcessRunner();
        $updater = new NpmDependencyUpdater($runner, '/project', 'npm');

        $updater->update(securityOnly: true);

        self::assertSame(['audit', 'fix', '--no-fund'], $runner->calls[0]['arguments']);
        self::assertNotContains('--force', $runner->calls[0]['arguments']);
    }

    public function test_npm_outdated_non_zero_exit_is_returned_for_update_discovery(): void
    {
        $runner = new RecordingProcessRunner(successful: false, exitCode: 1);
        $updater = new NpmDependencyUpdater($runner, '/project', 'npm');

        $result = $updater->check();

        self::assertFalse($result->successful);
        self::assertSame(1, $result->exitCode);
    }

    public function test_update_failure_is_reported(): void
    {
        $runner = new RecordingProcessRunner(successful: false, exitCode: 2, stderr: 'lockfile error');
        $updater = new ComposerDependencyUpdater($runner, '/project', 'composer');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('lockfile error');

        $updater->update();
    }
}

/** Minimal fake process runner used to assert commands without starting processes. */
final class RecordingProcessRunner implements ProcessRunnerInterface
{
    /** @var list<array{binary: string, arguments: list<string>, workingDirectory: string, manager: string, operation: string}> */
    public array $calls = [];

    public function __construct(
        private readonly bool $successful = true,
        private readonly int $exitCode = 0,
        private readonly string $stdout = '',
        private readonly string $stderr = '',
    ) {
    }

    public function run(string $binary, array $arguments, string $workingDirectory, string $manager, string $operation): DependencyUpdateResult
    {
        $this->calls[] = compact('binary', 'arguments', 'workingDirectory', 'manager', 'operation');

        return new DependencyUpdateResult($manager, $operation, $this->successful, $this->exitCode, $this->stdout, $this->stderr, 0.001);
    }
}
