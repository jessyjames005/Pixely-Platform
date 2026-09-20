<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use Illuminate\Console\Command;

final class ExtensionMigrateCommand extends Command
{
    protected $signature = 'pixely:extension:migrate {extension : Extension directory name}';

    protected $description = 'Run pending database migrations for a Pixely extension.';

    public function __construct(
        private readonly ExtensionMigrationRunner $runner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->runner->migrate($this->argument('extension'), $this);

        return self::SUCCESS;
    }
}
