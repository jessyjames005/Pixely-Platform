<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Registry\Services\RegistryManifestImportService;
use Illuminate\Console\Command;
use Throwable;

final class ImportRegistryManifestCommand extends Command
{
    protected $signature = 'pixely:registry:import {manifest : Path to a locally controlled signed release manifest}';

    protected $description = 'Import a trusted signed release manifest into the local Registry';

    public function handle(RegistryManifestImportService $importService): int
    {
        $path = (string) $this->argument('manifest');
        if (!is_file($path) || !is_readable($path)) {
            $this->error(sprintf('Unable to read manifest file: %s', $path));

            return self::FAILURE;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->error(sprintf('Unable to read manifest file: %s', $path));

            return self::FAILURE;
        }

        try {
            $imported = $importService->import($contents);
        } catch (Throwable $exception) {
            $this->error(sprintf('Manifest import failed: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        if ($imported) {
            $this->info('Signed release manifest imported.');
        } else {
            $this->info('Signed release manifest is already present; no changes made.');
        }

        return self::SUCCESS;
    }
}
