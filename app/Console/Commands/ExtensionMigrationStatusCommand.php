<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use Illuminate\Console\Command;

final class ExtensionMigrationStatusCommand extends Command
{
    protected $signature = 'pixely:extension:migration-status {extension}';

    protected $description = 'Display database migration status for a Pixely extension.';

    public function __construct(
        private readonly ExtensionMigrationRunner $runner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $rows = $this->runner->status($this->argument('extension'));

        if ($rows === []) {
            $this->warn('No migrations found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Migration', 'Status'],
            array_map(
                static fn (array $row): array => [
                    $row['migration'],
                    $row['status'],
                ],
                $rows,
            ),
        );

        return self::SUCCESS;
    }
}
