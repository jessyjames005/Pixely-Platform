<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Manager\ExtensionManager;
use Illuminate\Console\Command;

/**
 * Disable a Pixely extension.
 */
final class DisableExtensionCommand extends Command
{
    /**
     * Command signature.
     */
    protected $signature = 'pixely:disable {extension}';

    /**
     * Command description.
     */
    protected $description = 'Disable a Pixely extension.';

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

        $this->manager->disable($extension);

        $this->info(
            "Extension [{$extension}] disabled."
        );

        return self::SUCCESS;
    }
}
