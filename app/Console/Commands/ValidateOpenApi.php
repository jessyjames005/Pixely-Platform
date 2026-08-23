<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Validates the generated OpenAPI specification.
 */
final class ValidateOpenApi extends Command
{
    /**
     * Artisan command signature.
     */
    protected $signature = 'openapi:validate';

    /**
     * Artisan command description.
     */
    protected $description = 'Validate the generated OpenAPI specification.';

    /**
     * Path to the generated OpenAPI specification.
     */
    private const SPECIFICATION_PATH = 'docs/api/openapi.yml';

    /**
     * Validate the generated OpenAPI specification.
     */
    public function handle(): int
    {
        $path = base_path(self::SPECIFICATION_PATH);

        if (! $this->specificationExists($path)) {
            return self::FAILURE;
        }

        return $this->runValidator($path);
    }

    /**
     * Check that the generated specification exists.
     */
    private function specificationExists(string $path): bool
    {
        if (is_file($path)) {
            return true;
        }

        $this->error(
            'OpenAPI specification not found: ' . self::SPECIFICATION_PATH,
        );

        $this->line(
            'Run "php artisan openapi:generate" before validating the specification.',
        );

        return false;
    }

    /**
     * Run the OpenAPI specification validator.
     */
    private function runValidator(string $path): int
    {
        $process = new Process([
            base_path('vendor/bin/php-openapi'),
            'validate',
            $path,
        ]);

        $process->run();

        if ($process->isSuccessful()) {
            $this->info(
                'OpenAPI specification is valid.',
            );

            return self::SUCCESS;
        }

        $this->error(
            'OpenAPI specification validation failed.',
        );

        $output = trim(
            $process->getErrorOutput() . $process->getOutput(),
        );

        if ($output !== '') {
            $this->line($output);
        }

        return self::FAILURE;
    }
}
