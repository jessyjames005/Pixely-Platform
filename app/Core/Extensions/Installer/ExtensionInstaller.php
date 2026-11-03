<?php

declare(strict_types=1);

namespace App\Core\Extensions\Installer;

use App\Core\Extensions\Audit\ExtensionAuditLogger;
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Permissions\ExtensionPermissionSynchronizer;
use App\Core\Extensions\Versioning\ExtensionUpgradeRunner;
use App\Core\Extensions\Versioning\ExtensionVersionRepository;
use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Update\ExtensionUpdatePlanner;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Orchestrates the full install/update/uninstall flow for extensions
 * uploaded as a zip package.
 *
 * Install/update failures attempt filesystem rollback and report rollback
 * errors. Database mutations are not automatically rolled back.
 */
final class ExtensionInstaller implements ExtensionInstallerInterface
{
    private const EXTENSIONS_BASE_PATH = 'app/Extensions';

    public function __construct(
        private readonly ExtensionPackageValidator $validator,
        private readonly ExtensionAuditLogger $auditLogger,
        private readonly ExtensionPermissionSynchronizer $permissionSynchronizer,
        private readonly ExtensionUpgradeRunner $upgradeRunner,
        private readonly ExtensionMigrationRunner $migrationRunner,
    ) {
    }

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
        if (! is_dir($stagingDir)) {
            throw new \RuntimeException("Could not create extension staging directory [{$stagingDir}].");
        }

        $filesystem = new ExtensionInstallerFilesystem();
        $targetPath = null;
        $backupPath = null;
        $replacementAttempted = false;
        $databaseWorkStarted = false;
        $committed = false;
        $primaryFailure = null;
        $result = null;
        $auditExtensionId = null;
        $auditVersion = null;
        $failureStage = 'package_validation';

        try {
            $this->validator->extractSafely($zipPath, $stagingDir);
            $manifest = $this->validator->locateAndValidateManifest($stagingDir);
            $auditExtensionId = $manifest['id'];
            $auditVersion = $manifest['version'];
            $failureStage = 'release_validation';

            if ($mode === 'update' && $manifest['id'] !== $expectedId) {
                throw new \RuntimeException(
                    "Package extension id [{$manifest['id']}] does not match the extension being updated [{$expectedId}].",
                );
            }

            if ($mode === 'update') {
                $versionRepository = app(ExtensionVersionRepository::class);
                $installedVersions = $this->discoverInstalledVersions();
                $installedVersions[$manifest['id']] = $versionRepository->find($manifest['id']) ?? ($installedVersions[$manifest['id']] ?? '0.0.0');

                $candidate = new ExtensionManifest(
                    id: $manifest['id'],
                    name: $manifest['name'],
                    version: $manifest['version'],
                    minimum_kernel_version: $manifest['minimum_kernel_version'],
                    class: $manifest['class'],
                    path: $manifest['root'],
                    dependencies: $manifest['requires'],
                );

                app(ExtensionUpdatePlanner::class)->plan(
                    $candidate,
                    $installedVersions,
                    $manifest['require_versions'],
                    $manifest['conflicts'],
                    $this->discoverInstalledDependencyGraph(),
                );
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
            $failureStage = 'file_replacement';
            $backupPath = $alreadyExists ? $targetPath . '.backup-' . bin2hex(random_bytes(6)) : null;

            if ($backupPath !== null) {
                $filesystem->moveDirectory($targetPath, $backupPath, 'Backing up the installed extension');
            }

            $replacementAttempted = true;
            $filesystem->moveDirectory($manifest['root'], $targetPath, 'Installing the extension files');

            $failureStage = 'extension_boot';
            $this->refreshAutoloader();

            $this->assertClassIsValidExtension($manifest['class'], $targetPath, $backupPath);

            // Sync declared permissions now that the extension's class is
            // verified and autoloadable.
            $failureStage = 'permissions';
            $extensionInstance = new ($manifest['class'])();
            $this->permissionSynchronizer->sync($extensionInstance);

            // Keep the backup until migrations and upgrade steps have completed.
            $databaseWorkStarted = true;
            $failureStage = 'migrations';
            $this->migrationRunner->migrate($manifest['id']);

            if ($mode === 'install') {
                $this->upgradeRunner->recordFreshInstall($manifest['id'], $manifest['version']);
            } else {
                $failureStage = 'upgrade';
                $appliedSteps = $this->upgradeRunner->upgrade($extensionInstance, $manifest['version']);
            }

            $committed = true;
            if ($backupPath !== null) {
                $failureStage = 'file_replacement';
                $filesystem->deleteDirectory($backupPath, 'Removing the previous extension backup');
            }

            $this->auditLogger->logSafely(
                $manifest['id'],
                $mode,
                $manifest['version'],
                ['outcome' => 'success', 'stage' => $mode],
                $userId,
            );

            $result = [
                'id' => $manifest['id'],
                'name' => $manifest['name'],
                'version' => $manifest['version'],
            ];
        } catch (\Throwable $exception) {
            $primaryFailure = $exception;
        }

        $rollbackErrors = [];
        $filesystemMayBeInconsistent = false;
        if (! $committed) {
            $rollbackResult = $filesystem->rollback(
                $targetPath,
                $backupPath,
                $replacementAttempted,
                $stagingDir,
                fn () => $this->refreshAutoloader(),
            );
            $rollbackErrors = $rollbackResult['errors'];
            $filesystemMayBeInconsistent = $rollbackResult['filesystem_may_be_inconsistent'];
        } else {
            try {
                $filesystem->deleteDirectory($stagingDir, 'Removing extension staging files');
            } catch (\Throwable $exception) {
                $rollbackErrors[] = $exception->getMessage();
            }
        }

        if ($primaryFailure !== null) {
            if ($auditExtensionId !== null && $auditVersion !== null) {
                $this->auditLogger->logSafely(
                    $auditExtensionId,
                    $mode,
                    $auditVersion,
                    ['outcome' => 'failure', 'stage' => $failureStage],
                    $userId,
                );
            }

            if ($rollbackErrors !== [] || $databaseWorkStarted || $committed) {
                throw $filesystem->failure(
                    $primaryFailure,
                    $rollbackErrors,
                    $backupPath,
                    $databaseWorkStarted,
                    $committed,
                    $manifest['id'] ?? null,
                    $filesystemMayBeInconsistent,
                );
            }

            throw $primaryFailure;
        }

        if ($rollbackErrors !== []) {
            throw new \RuntimeException(
                "Extension installation completed, but cleanup failed:\n- " . implode("\n- ", $rollbackErrors),
            );
        }

        return $result;
    }


    /**
     * Discover installed extension versions from their manifests. The tracked
     * version repository remains authoritative for the extension being updated.
     *
     * @return array<string, string>
     */
    private function discoverInstalledVersions(): array
    {
        $versions = [];
        $base = base_path(self::EXTENSIONS_BASE_PATH);

        foreach (File::directories($base) as $directory) {
            $manifestPath = $directory . DIRECTORY_SEPARATOR . 'extension.php';
            if (! is_file($manifestPath)) {
                continue;
            }

            $data = include $manifestPath;
            if (is_array($data) && is_string($data['id'] ?? null) && is_string($data['version'] ?? null)) {
                $versions[$data['id']] = $data['version'];
            }
        }

        return $versions;
    }


    /**
     * Read dependency declarations for installed extensions to detect cycles
     * introduced by an update candidate before replacing any live files.
     *
     * @return array<string, string[]>
     */
    private function discoverInstalledDependencyGraph(): array
    {
        $graph = [];
        $base = base_path(self::EXTENSIONS_BASE_PATH);

        foreach (File::directories($base) as $directory) {
            $manifestPath = $directory . DIRECTORY_SEPARATOR . 'extension.php';
            if (! is_file($manifestPath)) {
                continue;
            }

            $data = include $manifestPath;
            if (is_array($data) && is_string($data['id'] ?? null) && is_array($data['requires'] ?? [])) {
                $graph[$data['id']] = array_values(array_filter($data['requires'], 'is_string'));
            }
        }

        return $graph;
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
            throw new \RuntimeException(
                "The declared class [{$class}] does not exist or does not implement ExtensionInterface. Installation will be rolled back.",
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
