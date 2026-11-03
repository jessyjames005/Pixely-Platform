<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Installer\ExtensionInstallerInterface;
use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use App\Core\Updates\Contracts\UpdateRegistryInterface;
use App\Core\Updates\Contracts\UpdateVerifierInterface;
use App\Core\Updates\DTO\ReleaseManifestValue;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;
use App\Core\Updates\Services\ExtensionReleasePreflight;
use App\Core\Updates\Services\RuntimeCompatibilityChecker;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

/**
 * Installs or replaces one authenticated extension release from quarantine.
 */
final class InstallExtensionReleaseCommand extends Command
{
    protected $signature = 'pixely:extension:install
        {package : Exact canonical package identifier, e.g. pixely/gallery}
        {version : Exact release version, using Semantic Versioning}
        {artifact : Exact artifact path printed by pixely:extension:download}
        {--channel=stable : Release channel: stable, beta, or dev}
        {--yes : Explicitly approve installation without an interactive prompt}';

    protected $description = 'Install or replace an extension using a verified quarantined release artifact.';

    public function __construct(
        private readonly UpdateRegistryInterface $registry,
        private readonly UpdateVerifierInterface $verifier,
        private readonly RuntimeCompatibilityChecker $compatibility,
        private readonly ExtensionReleasePreflight $preflight,
        private readonly InstalledExtensionInventoryInterface $inventory,
        private readonly ExtensionInstallerInterface $installer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $packageId = (string) $this->argument('package');
        $version = (string) $this->argument('version');
        $artifactPath = (string) $this->argument('artifact');

        try {
            ReleaseManifestValue::assertPackageId($packageId, 'package');
            ReleaseManifestValue::assertSemver($version, 'version');
        } catch (\InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::INVALID;
        }

        $channel = UpdateChannel::tryFrom((string) $this->option('channel'));
        if ($channel === null) {
            $this->components->error('--channel must be one of: stable, beta, or dev.');

            return self::INVALID;
        }

        $inspectionDirectory = null;
        try {
            $artifactPath = $this->assertQuarantinedArtifact($artifactPath);
            $candidate = $this->registry->findRelease(
                UpdateTargetType::Extension,
                $packageId,
                $version,
            );

            if ($candidate === null) {
                throw new RuntimeException('The requested extension release was not found in the Registry.');
            }

            if (
                $candidate->type !== UpdateTargetType::Extension
                || $candidate->packageId !== $packageId
                || $candidate->version !== $version
            ) {
                throw new RuntimeException('The Registry returned a release with an unexpected identity.');
            }

            if ($candidate->channel !== $channel) {
                throw new RuntimeException('The requested release does not match the selected channel.');
            }

            if (!$this->compatibility->supports($candidate, $this->compatibility->runtimeVersions())) {
                throw new RuntimeException('The requested release is not compatible with this runtime.');
            }

            $parts = explode('/', $packageId);
            $extensionId = (string) end($parts);
            $installed = $this->inventory->find($extensionId);
            if ($installed !== null && $installed->packageId !== $packageId) {
                throw new RuntimeException(
                    "Installed extension [{$extensionId}] belongs to Registry package "
                    . "[{$installed->packageId}], not [{$packageId}].",
                );
            }

            $this->verifier->verify($artifactPath, $candidate->artifact);
            $inspectionDirectory = $this->createInspectionDirectory(dirname($artifactPath));
            $this->preflight->validate($artifactPath, $inspectionDirectory, $packageId, $version);
            $cleanupFailures = $this->cleanupInspectionDirectory($inspectionDirectory);
            $inspectionDirectory = null;
            if ($cleanupFailures !== []) {
                throw new RuntimeException(
                    'Unable to clean temporary extension inspection files: '
                    . implode(' ', $cleanupFailures),
                );
            }
        } catch (Throwable $exception) {
            $cleanupFailures = $inspectionDirectory === null
                ? []
                : $this->cleanupInspectionDirectory($inspectionDirectory);

            $this->components->error('Extension installation failed: ' . $exception->getMessage());
            foreach ($cleanupFailures as $cleanupFailure) {
                $this->components->error('Cleanup failed: ' . $cleanupFailure);
            }

            return self::FAILURE;
        }

        $action = $installed === null ? 'install' : 'replace';
        if (!$this->option('yes')) {
            if (!$this->input->isInteractive()) {
                $this->components->error(
                    'Use --yes to explicitly approve this installation in a non-interactive environment.',
                );

                return self::FAILURE;
            }

            $this->components->warn(
                'This operation may execute publisher PHP, replace extension files, and run migrations or upgrade '
                . 'steps. Database changes may not be reversible; ensure you have a database backup.',
            );
            if (
                !$this->confirm(
                    "Proceed to {$action} extension [{$extensionId}] version [{$version}] from Registry package "
                    . "[{$packageId}]?",
                    false,
                )
            ) {
                $this->components->info('Installation cancelled; no extension files were changed.');

                return self::SUCCESS;
            }
        }

        try {
            $artifactPath = $this->assertQuarantinedArtifact($artifactPath);
            $this->verifier->verify($artifactPath, $candidate->artifact);

            $result = $installed === null
                ? $this->installer->install($artifactPath, null)
                : $this->installer->update($artifactPath, $extensionId, null);

            $this->components->info(
                "Extension [{$result['id']}] version [{$result['version']}] "
                . ($installed === null ? 'installed.' : 'replaced.'),
            );
            $this->line("Verified artifact retained in quarantine: {$artifactPath}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error('Extension installation failed: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }

    private function assertQuarantinedArtifact(string $path): string
    {
        if ($path === '' || str_contains($path, "\0") || !str_starts_with($path, DIRECTORY_SEPARATOR)) {
            throw new RuntimeException(
                'The artifact path must be the exact absolute path printed by the download command.',
            );
        }

        $quarantineRoot = storage_path('app/extension-release-quarantine');
        $realRoot = realpath($quarantineRoot);
        if (
            is_link($quarantineRoot)
            || !is_dir($quarantineRoot)
            || $realRoot === false
            || $realRoot !== rtrim($quarantineRoot, DIRECTORY_SEPARATOR)
        ) {
            throw new RuntimeException('The extension release quarantine root is missing or uses a symbolic link.');
        }

        if (is_link($path) || !is_file($path)) {
            throw new RuntimeException('The artifact path must refer to a regular, non-symbolic-link file.');
        }

        $realPath = realpath($path);
        $parent = dirname($path);
        $realParent = realpath($parent);
        if (
            $realPath === false
            || $realPath !== $path
            || $realParent === false
            || is_link($parent)
            || $realParent !== $parent
            || dirname($realParent) !== $realRoot
            || preg_match('/^[a-f0-9]{32}$/', basename($realParent)) !== 1
        ) {
            throw new RuntimeException(
                'The artifact must be a regular file exactly one directory below the private release quarantine.',
            );
        }

        if (!is_readable($realPath) || filesize($realPath) === false || filesize($realPath) < 1) {
            throw new RuntimeException('The quarantined artifact is empty or unreadable.');
        }

        return $realPath;
    }

    private function createInspectionDirectory(string $artifactDirectory): string
    {
        $path = $artifactDirectory . DIRECTORY_SEPARATOR . 'inspection';
        if (file_exists($path) || is_link($path) || !mkdir($path, 0700) || !chmod($path, 0700)) {
            throw new RuntimeException('Unable to create a private extension inspection directory.');
        }

        $realPath = realpath($path);
        if (
            $realPath === false
            || dirname($realPath) !== $artifactDirectory
        ) {
            $this->cleanupInspectionDirectory($path);
            throw new RuntimeException('Unable to resolve the private extension inspection directory.');
        }

        return $realPath;
    }

    /**
     * @return list<string>
     */
    private function cleanupInspectionDirectory(string $inspectionDirectory): array
    {
        if (is_link($inspectionDirectory)) {
            return ['Refused to clean an inspection path that is a symbolic link.'];
        }
        if (!is_dir($inspectionDirectory)) {
            return file_exists($inspectionDirectory)
                ? ['The inspection path is no longer a directory.']
                : [];
        }

        $failures = [];
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $inspectionDirectory,
                    \FilesystemIterator::SKIP_DOTS,
                ),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $entry) {
                $entryPath = $entry->getPathname();
                if ($entry->isLink() || $entry->isFile()) {
                    if (!unlink($entryPath)) {
                        $failures[] = "Unable to remove inspected package file [{$entryPath}].";
                    }
                } elseif ($entry->isDir() && !rmdir($entryPath)) {
                    $failures[] = "Unable to remove inspection directory [{$entryPath}].";
                }
            }
        } catch (Throwable $exception) {
            $failures[] = 'Unable to inspect temporary package files: ' . $exception->getMessage();
        }

        if (!rmdir($inspectionDirectory)) {
            $failures[] = "Unable to remove the inspection directory [{$inspectionDirectory}].";
        }

        return $failures;
    }
}
