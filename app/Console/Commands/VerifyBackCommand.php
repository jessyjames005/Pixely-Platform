<?php

/**
 * Backend CI verification command (lot MU3 tooling).
 *
 * Runs the same checks CI runs — PHP Code Style, PHPStan static analysis and
 * the PHPUnit/Pest suite — from a single Artisan command so the gate can be
 * reproduced quickly before a lot is delivered. It shells out to the existing
 * composer scripts and `artisan test` so there is a single source of truth for
 * what "green" means.
 *
 * Usage (inside the PHP container):
 *   docker exec pixely_app php artisan pixely:verify-back
 *   docker exec pixely_app php artisan pixely:verify-back --timeout=1800
 */

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Process;

final class VerifyBackCommand extends Command
{
    protected $signature = 'pixely:verify-back {--timeout=1200 : Per-command timeout in seconds}';

    protected $description = 'Run the backend CI verification suite (phpcs, PHPStan, tests)';

    /**
     * Execute every backend check, then print a PASS/FAIL summary.
     *
     * Returns FAILURE if any step fails so the command can gate a commit.
     */
    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');

        /** @var array<string, array<int, string>> $steps */
        $steps = [
            'PHP Code Style (composer cs:check)' => ['composer', 'cs:check'],
            'PHPStan (composer phpstan)' => ['composer', 'phpstan'],
            'Tests (php artisan test)' => ['php', 'artisan', 'test'],
        ];

        $this->line('Running backend CI verification...');
        $this->newLine();

        $rows = [];
        $allPassed = true;

        foreach ($steps as $label => $command) {
            $result = $this->runProcess($command, $timeout);

            $passed = $result['exitCode'] === 0;
            $allPassed = $allPassed && $passed;

            $rows[] = [
                $label,
                $passed ? 'PASS' : 'FAIL',
                $passed ? '' : $this->tail($result['output']),
            ];

            if (! $passed) {
                // Surface the failing output immediately so the user can act.
                $this->error($label . ' failed:');
                $this->line($result['output']);
                $this->newLine();
            }
        }

        $this->table(['Check', 'Status', 'Details (on failure)'], $rows);
        $this->newLine();

        if ($allPassed) {
            $this->info('Backend CI: GREEN');

            return self::SUCCESS;
        }

        $this->error('Backend CI: RED');

        return self::FAILURE;
    }

    /**
     * Run a command as a subprocess and capture its exit code and output.
     *
     * @param array<int, string> $command
     * @return array{exitCode: int, output: string}
     */
    private function runProcess(array $command, int $timeout): array
    {
        $process = new Process($command, base_path(), null, null, $timeout);

        try {
            $process->run();
        } catch (ProcessStartFailedException $exception) {
            // The binary itself is missing (e.g. composer not on PATH).
            return ['exitCode' => 1, 'output' => $exception->getMessage()];
        }

        return [
            'exitCode' => $process->getExitCode() ?? 1,
            'output' => trim($process->getErrorOutput() . "\n" . $process->getOutput()),
        ];
    }

    /**
     * Keep only the last lines of a long output so the summary table stays
     * readable while still showing the relevant failure context.
     */
    private function tail(string $output, int $lines = 20): string
    {
        $all = preg_split('/\r\n|\r|\n/', $output) ?: [];

        return implode("\n", array_slice($all, -$lines));
    }
}
