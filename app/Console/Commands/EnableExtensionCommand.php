<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Manager\ExtensionManager;
use Illuminate\Console\Command;

/**
 * Enable a Pixely extension.
 */
final class EnableExtensionCommand extends Command
{
    /**
     * Command signature.
     */
    protected $signature = 'pixely:enable {extension}';

    /**
     * Command description.
     */
    protected $description = 'Enable a Pixely extension.';

    public function __construct(
        private readonly ExtensionManager $manager,
    ) {
        parent::__construct();
    }

    /**
     * Execute command.
     */
    public function handle(): int
    {
        $extension = $this->argument('extension');

        if (! $this->manager->has($extension)) {
            $this->error(
                "Extension [{$extension}] not found."
            );

            return self::FAILURE;
        }

        $this->manager->enable($extension);

        $this->info(
            "Extension [{$extension}] enabled."
        );

        return self::SUCCESS;
    }
}
