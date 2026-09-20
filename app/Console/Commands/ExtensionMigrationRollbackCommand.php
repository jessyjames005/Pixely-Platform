<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use Illuminate\Console\Command;

final class ExtensionMigrationRollbackCommand extends Command
{
    protected $signature = 'pixely:extension:migration-rollback {extension}';

    protected $description = 'Roll back the latest migration batch for a Pixely extension.';

    public function __construct(
        private readonly ExtensionMigrationRunner $runner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->runner->rollback($this->argument('extension'));

        $this->info('Extension migration rollback completed.');

        return self::SUCCESS;
    }
}
