<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Migration\ExtensionStateJsonImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/** Import legacy extension runtime state from storage/pixely/extensions.json. */
final class ImportExtensionStateCommand extends Command
{
    protected $signature = 'pixely:extensions:import-state {--path= : Optional path to the legacy JSON state file}';

    protected $description = 'Import legacy JSON extension states into the database without overwriting existing database state.';

    public function handle(ExtensionStateJsonImporter $importer): int
    {
        if (! Schema::hasTable('extension_states')) {
            $this->error('The extension_states table does not exist. Run php artisan migrate first.');

            return self::FAILURE;
        }

        $path = $this->option('path') ?: storage_path('pixely/extensions.json');

        if (! is_file($path)) {
            $this->error("Extension state file not found: {$path}");

            return self::FAILURE;
        }

        $result = $importer->import($path);
        $this->info("Imported {$result['imported']} extension state(s); skipped {$result['skipped']} existing or invalid record(s).");

        // Existing rows are expected on reruns, so skipped records are not a failure.
        return self::SUCCESS;
    }
}
