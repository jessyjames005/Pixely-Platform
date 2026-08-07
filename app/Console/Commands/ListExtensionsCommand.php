<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Manager\ExtensionManager;
use Illuminate\Console\Command;

/**
 * Display registered extensions.
 */
final class ListExtensionsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'pixely:extensions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display registered Pixely extensions.';

    public function __construct(
        private readonly ExtensionManager $manager,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rows = [];

        foreach ($this->manager->all() as $extension) {
            $manifest = $extension->manifest();

            $rows[] = [
                $manifest->id,
                $manifest->version,
                $this->manager->isEnabled($manifest->id)
                    ? 'Enabled'
                    : 'Disabled',
            ];
        }

        $this->table(
            ['ID', 'Version', 'Status'],
            $rows,
        );

        return self::SUCCESS;
    }
}
