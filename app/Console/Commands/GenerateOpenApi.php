<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Generates the OpenAPI specification from PHP attributes.
 */
final class GenerateOpenApi extends Command
{
    /**
     * Artisan command signature.
     */
    protected $signature = 'openapi:generate';

    /**
     * Artisan command description.
     */
    protected $description = 'Generate the OpenAPI specification from PHP attributes.';

    /**
     * Generate the OpenAPI specification.
     */
    public function handle(): int
    {
        $outputFile = base_path('docs/api/openapi.yml');

        $this->info('Generating OpenAPI specification...');

        $process = new Process([
            base_path('vendor/bin/openapi'),
            app_path('Core'),
            app_path('Extensions'),
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('OpenAPI generation failed.');
            $this->line($process->getErrorOutput());

            return self::FAILURE;
        }

        file_put_contents(
            $outputFile,
            $process->getOutput(),
        );

        $this->info('OpenAPI specification generated successfully.');
        $this->line($outputFile);

        return self::SUCCESS;
    }
}
