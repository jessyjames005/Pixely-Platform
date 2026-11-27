<?php

/**
 * Frontend CI verification command (lot MU3 tooling).
 *
 * Runs the same checks CI runs — ESLint, Vitest, the TypeScript/Vue typecheck
 * and the Vite production build — from a single Artisan command.
 *
 * The PHP container has no Node toolchain, so by default the checks are
 * delegated to the Node container via `docker exec`. When the command itself
 * runs somewhere the Node toolchain is local (e.g. the host or a CI runner),
 * pass `--local` to invoke `npm` directly instead.
 *
 * Usage (inside the PHP container, delegating to the Node container):
 *   docker exec pixely_app php artisan pixely:verify-front
 *   docker exec pixely_app php artisan pixely:verify-front --container=pixely_node
 *   docker exec pixely_app php artisan pixely:verify-front --local
 */

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Process;

final class VerifyFrontCommand extends Command
{
    protected $signature = 'pixely:verify-front
        {--container=pixely_node : Node container used when delegating via docker}
        {--local : Run npm directly instead of delegating to the Node container}
        {--timeout=1200 : Per-command timeout in seconds}';

    protected $description = 'Run the frontend CI verification suite (ESLint, Vitest, typecheck, build)';

    /**
     * Execute every frontend check, then print a PASS/FAIL summary.
     *
     * Returns FAILURE if any step fails so the command can gate a commit.
     */
    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');
        $local = (bool) $this->option('local');
        $container = (string) $this->option('container');

        // Build the command prefix: either `docker exec <container>` or nothing
        // (local npm). Keeping this in one place makes the delegation explicit.
        $prefix = $local ? [] : ['docker', 'exec', $container];

        if (! $local && ! $this->dockerAvailable()) {
            $this->error("Docker CLI not available; cannot reach the [{$container}] Node container.");
            $this->line('Run this command from the host, or pass --local where npm is installed.');

            return self::FAILURE;
        }

        /** @var array<string, array<int, string>> $steps */
        $steps = [
            'ESLint (npm run lint)' => [...$prefix, 'npm', 'run', 'lint'],
            'Vitest (npm test)' => [...$prefix, 'npm', 'test'],
            'Typecheck (npm run typecheck)' => [...$prefix, 'npm', 'run', 'typecheck'],
            'Build (npm run build)' => [...$prefix, 'npm', 'run', 'build'],
        ];

        $this->line('Running frontend CI verification' . ($local ? ' (local npm)...' : " (via {$container})..."));
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
                $this->error($label . ' failed:');
                $this->line($result['output']);
                $this->newLine();
            }
        }

        $this->table(['Check', 'Status', 'Details (on failure)'], $rows);
        $this->newLine();

        if ($allPassed) {
            $this->info('Frontend CI: GREEN');

            return self::SUCCESS;
        }

        $this->error('Frontend CI: RED');

        return self::FAILURE;
    }

    /**
     * Whether the docker CLI can be invoked in the current environment.
     */
    private function dockerAvailable(): bool
    {
        $process = new Process(['docker', '--version'], base_path(), null, null, 10);
        $process->run();

        return $process->getExitCode() === 0;
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
