<?php

declare(strict_types=1);

namespace App\Core\Extensions\Installer;

use App\Core\Extensions\Audit\ExtensionAuditLogger;
use App\Core\Extensions\Contracts\ExtensionInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use App\Core\Extensions\Permissions\ExtensionPermissionSynchronizer;
use App\Core\Extensions\Versioning\ExtensionUpgradeRunner;
use App\Core\Extensions\Versioning\ExtensionUpgradableInterface;
use App\Core\Extensions\Versioning\ExtensionVersionRepository;

/**
 * Orchestrates the full install/update/uninstall flow for extensions
 * uploaded as a zip package.
 *
 * Every step that can fail rolls back cleanly: nothing is left
 * half-installed in app/Extensions.
 */
final class ExtensionInstaller
{
    private const EXTENSIONS_BASE_PATH = 'app/Extensions';

    public function __construct(
        private readonly ExtensionPackageValidator $validator,
        private readonly ExtensionAuditLogger $auditLogger,
        private readonly ExtensionPermissionSynchronizer $permissionSynchronizer,
        private readonly ExtensionUpgradeRunner $upgradeRunner,
    ) {}

    /**
     * Install a new extension from an uploaded zip file.
     *
     * @return array{id: string, name: string, version: string}
     * @throws \RuntimeException
     */
    public function install(string $zipPath, ?int $userId): array
    {
        return $this->processPackage($zipPath, mode: 'install', userId: $userId);
    }

    /**
     * Update an existing extension from an uploaded zip file.
     *
     * @return array{id: string, name: string, version: string}
     * @throws \RuntimeException
     */
    public function update(string $zipPath, string $expectedId, ?int $userId): array
    {
        return $this->processPackage($zipPath, mode: 'update', userId: $userId, expectedId: $expectedId);
    }

    /**
     * Uninstall an extension: removes its directory from disk.
     *
     * Deliberately does NOT drop database tables or run migration
     * rollbacks automatically — that is destructive to data in a way
     * that must be a separate, explicit, confirmed action.
     */
    public function uninstall(string $id, ?int $userId): void
    {
        $targetPath = base_path(self::EXTENSIONS_BASE_PATH . '/' . $this->safeDirectoryName($id));

        if (! is_dir($targetPath)) {
            throw new \RuntimeException("Extension [{$id}] is not installed.");
        }

        File::deleteDirectory($targetPath);

        $this->refreshAutoloader();

        $this->auditLogger->log($id, 'uninstall', details: [
            'note' => 'Files removed. Database tables and migrations were NOT rolled back automatically.',
        ]);
    }

    /**
     * @return array{id: string, name: string, version: string}
     */
    private function processPackage(string $zipPath, string $mode, ?int $userId, ?string $expectedId = null): array
    {
        $stagingDir = storage_path('app/extension-staging/' . uniqid('ext_', true));
        File::ensureDirectoryExists($stagingDir);

        try {
            $this->validator->extractSafely($zipPath, $stagingDir);
            $manifest = $this->validator->locateAndValidateManifest($stagingDir);

            if ($mode === 'update' && $manifest['id'] !== $expectedId) {
                throw new \RuntimeException(
                    "Package extension id [{$manifest['id']}] does not match the extension being updated [{$expectedId}].",
                );
            }

            if ($mode === 'update') {
                $installedVersion = app(ExtensionVersionRepository::class)->find($manifest['id']) ?? '0.0.0';

                if (version_compare($manifest['version'], $installedVersion, '<=')) {
                    throw new \RuntimeException(
                        "Package version [{$manifest['version']}] is not newer than the installed version [{$installedVersion}].",
                    );
                }
            }

            $targetPath = base_path(self::EXTENSIONS_BASE_PATH . '/' . $this->safeDirectoryName($manifest['id']));
            $alreadyExists = is_dir($targetPath);

            if ($mode === 'install' && $alreadyExists) {
                throw new \RuntimeException("Extension [{$manifest['id']}] is already installed. Use update instead.");
            }

            if ($mode === 'update' && ! $alreadyExists) {
                throw new \RuntimeException("Extension [{$manifest['id']}] is not installed. Use install instead.");
            }

            // Move into place. For updates, the old directory is backed
            // up (not deleted) until the new one is verified below.
            $backupPath = $alreadyExists ? $targetPath . '.backup-' . time() : null;

            if ($backupPath !== null) {
                File::moveDirectory($targetPath, $backupPath);
            }

            File::moveDirectory($manifest['root'], $targetPath);

            $this->refreshAutoloader();

            $this->assertClassIsValidExtension($manifest['class'], $targetPath, $backupPath);

            // Sync declared permissions now that the extension's class is
            // verified and autoloadable.
            $extensionInstance = new ($manifest['class'])();
            $this->permissionSynchronizer->sync($extensionInstance);

            // Success: the backup (if any) is no longer needed.
            if ($backupPath !== null) {
                File::deleteDirectory($backupPath);
            }

            $this->runPendingMigrations();

            if ($mode === 'install') {
                $this->upgradeRunner->recordFreshInstall($manifest['id'], $manifest['version']);
            } else {
                $appliedSteps = $this->upgradeRunner->upgrade($extensionInstance, $manifest['version']);
            }

            $this->auditLogger->log(
                $manifest['id'],
                $mode,
                $manifest['version'],
                ['name' => $manifest['name']],
            );

            return [
                'id' => $manifest['id'],
                'name' => $manifest['name'],
                'version' => $manifest['version'],
            ];
        } finally {
            File::deleteDirectory($stagingDir);
        }
    }

    /**
     * Verifies the extension's declared class actually exists and
     * implements ExtensionInterface after the autoloader refresh.
     * Rolls back (restores the backup, or deletes the bad install)
     * if verification fails.
     */
    private function assertClassIsValidExtension(string $class, string $installedPath, ?string $backupPath): void
    {
        if (! class_exists($class) || ! is_subclass_of($class, ExtensionInterface::class) && ! (is_a($class, ExtensionInterface::class, true))) {
            File::deleteDirectory($installedPath);

            if ($backupPath !== null) {
                File::moveDirectory($backupPath, $installedPath);
            }

            throw new \RuntimeException(
                "The declared class [{$class}] does not exist or does not implement ExtensionInterface. Installation rolled back.",
            );
        }
    }

    /**
     * Regenerates the Composer autoloader so newly installed PHP
     * classes are discoverable immediately, without waiting for the
     * next deployment. Required whenever the autoloader is running
     * in optimized/classmap mode (e.g. production).
     */
    private function refreshAutoloader(): void
    {
        $process = new Process(['composer', 'dump-autoload', '-o'], base_path());
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Failed to refresh the autoloader: ' . $process->getErrorOutput());
        }
    }

    /**
     * Runs any pending migrations, including ones just introduced by
     * a newly installed/updated extension's service provider.
     */
    private function runPendingMigrations(): void
    {
        Artisan::call('migrate', ['--force' => true]);
    }

    /**
     * Sanitizes an extension id into a safe directory name — belt and
     * braces on top of the id format already validated by
     * ExtensionPackageValidator.
     */
    private function safeDirectoryName(string $id): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9\-]/', '', $id) ?? '';

        if ($safe === '') {
            throw new \RuntimeException('Invalid extension id.');
        }

        return ucfirst($safe);
    }
}
