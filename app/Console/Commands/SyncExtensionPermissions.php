<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Permissions\ExtensionPermissionSynchronizer;
use Illuminate\Console\Command;

/**
 * Synchronizes permissions declared by every registered extension.
 *
 * Run manually after upgrading Pixely (to backfill permissions for
 * extensions installed before this feature existed) or as part of a
 * deployment step.
 */
final class SyncExtensionPermissions extends Command
{
    protected $signature = 'pixely:sync-permissions {extension? : A specific extension id, or all if omitted}';

    protected $description = 'Sync permissions declared by installed extensions into the database';

    public function handle(ExtensionManager $manager, ExtensionPermissionSynchronizer $synchronizer): int
    {
        $targetId = $this->argument('extension');
        $extensions = $targetId !== null
            ? array_filter($manager->all(), fn ($ext) => $ext->manifest()->id === $targetId)
            : $manager->all();

        if ($extensions === []) {
            $this->error("Extension [{$targetId}] not found.");
            return self::FAILURE;
        }

        foreach ($extensions as $extension) {
            $created = $synchronizer->sync($extension);

            if ($created === []) {
                $this->line("[{$extension->manifest()->id}] — no new permissions.");
                continue;
            }

            $this->info("[{$extension->manifest()->id}] — created: " . implode(', ', $created));
        }

        return self::SUCCESS;
    }
}
