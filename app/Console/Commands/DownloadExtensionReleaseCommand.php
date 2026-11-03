<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Updates\Contracts\ArtifactSourceInterface;
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
 * Downloads one exact compatible extension release into private quarantine.
 */
final class DownloadExtensionReleaseCommand extends Command
{
    protected $signature = 'pixely:extension:download
        {package : Exact canonical package identifier, e.g. pixely/gallery}
        {version : Exact release version, using Semantic Versioning}
        {--channel=stable : Release channel: stable, beta, or dev}';

    protected $description = 'Download an exact extension release into quarantine without installing it.';

    public function __construct(
        private readonly UpdateRegistryInterface $registry,
        private readonly ArtifactSourceInterface $artifactSource,
        private readonly UpdateVerifierInterface $verifier,
        private readonly RuntimeCompatibilityChecker $compatibility,
        private readonly ExtensionReleasePreflight $preflight,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $packageId = (string) $this->argument('package');
        $version = (string) $this->argument('version');

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

        $stagedPath = null;
        $stagingDirectory = null;
        $inspectionDirectory = null;

        try {
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

            $quarantineRoot = $this->prepareStagingDirectory(
                storage_path('app/extension-release-quarantine'),
            );
            $stagingDirectory = $this->createDownloadDirectory($quarantineRoot);
            $stagedPath = $this->artifactSource->download($candidate->artifact, $stagingDirectory);
            $this->assertQuarantinedArtifact($stagedPath, $stagingDirectory);
            $this->verifier->verify($stagedPath, $candidate->artifact);
            $inspectionDirectory = $this->createInspectionDirectory($stagingDirectory);
            $this->preflight->validate($stagedPath, $inspectionDirectory, $packageId, $version);
            $cleanupFailures = $this->cleanupInspectionDirectory($inspectionDirectory);
            if ($cleanupFailures !== []) {
                throw new RuntimeException(
                    'Unable to clean temporary extension inspection files: '
                    . implode(' ', $cleanupFailures),
                );
            }
            $inspectionDirectory = null;
        } catch (Throwable $exception) {
            $cleanupFailures = [];
            if ($inspectionDirectory !== null) {
                $cleanupFailures = array_merge(
                    $cleanupFailures,
                    $this->cleanupInspectionDirectory($inspectionDirectory),
                );
            }
            if ($stagingDirectory !== null) {
                $cleanupFailures = array_merge(
                    $cleanupFailures,
                    $this->cleanupDownloadDirectory($stagingDirectory),
                );
            }

            $this->components->error('Extension release download failed: ' . $exception->getMessage());
            foreach ($cleanupFailures as $cleanupFailure) {
                $this->components->error('Cleanup failed: ' . $cleanupFailure);
            }

            return self::FAILURE;
        }

        $this->line("Verified artifact remains quarantined at: {$stagedPath}");
        $this->line(
            'Artifact verification and static package preflight succeeded; it remains quarantined and was not '
            . 'installed. Use pixely:extension:install with this exact path to re-verify and request installation.',
        );

        return self::SUCCESS;
    }

    private function prepareStagingDirectory(string $path): string
    {
        if (is_link($path)) {
            throw new RuntimeException('The private artifact staging directory must not be a symbolic link.');
        }

        if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
            throw new RuntimeException('Unable to create the private artifact staging directory.');
        }

        if (!chmod($path, 0700) || !is_writable($path)) {
            throw new RuntimeException('The private artifact staging directory is not securely writable.');
        }

        $realPath = realpath($path);
        if ($realPath === false) {
            throw new RuntimeException('Unable to resolve the private artifact staging directory.');
        }

        return $realPath;
    }

    private function createDownloadDirectory(string $quarantineRoot): string
    {
        $path = $quarantineRoot . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16));
        if (!mkdir($path, 0700)) {
            throw new RuntimeException('Unable to create a private directory for this artifact download.');
        }

        if (!chmod($path, 0700) || !is_writable($path)) {
            $cleanupFailure = $this->attemptCleanup(
                static fn (string $directory): bool => rmdir($directory),
                $path,
                'remove the temporary download directory',
            );
            throw new RuntimeException(
                'Unable to create a private directory for this artifact download.'
                . ($cleanupFailure === null ? '' : ' ' . $cleanupFailure),
            );
        }

        $realPath = realpath($path);
        if ($realPath === false) {
            $cleanupFailure = $this->attemptCleanup(
                static fn (string $directory): bool => rmdir($directory),
                $path,
                'remove the temporary download directory',
            );
            throw new RuntimeException(
                'Unable to resolve the private artifact download directory.'
                . ($cleanupFailure === null ? '' : ' ' . $cleanupFailure),
            );
        }

        return $realPath;
    }

    private function createInspectionDirectory(string $downloadDirectory): string
    {
        $path = $downloadDirectory . DIRECTORY_SEPARATOR . 'inspection';
        if (!mkdir($path, 0700) || !chmod($path, 0700)) {
            $cleanupFailures = $this->cleanupInspectionDirectory($path);
            throw new RuntimeException(
                'Unable to create a private extension inspection directory.'
                . ($cleanupFailures === [] ? '' : ' ' . implode(' ', $cleanupFailures)),
            );
        }

        $realPath = realpath($path);
        if (
            $realPath === false
            || !str_starts_with($realPath, $downloadDirectory . DIRECTORY_SEPARATOR)
        ) {
            $cleanupFailures = $this->cleanupInspectionDirectory($path);
            throw new RuntimeException(
                'Unable to resolve the private extension inspection directory.'
                . ($cleanupFailures === [] ? '' : ' ' . implode(' ', $cleanupFailures)),
            );
        }

        return $realPath;
    }

    private function assertQuarantinedArtifact(string $path, string $stagingDirectory): void
    {
        if (!is_file($path) || is_link($path)) {
            throw new RuntimeException('The artifact source did not return a regular staged file.');
        }

        $realPath = realpath($path);
        if (
            $realPath === false
            || !str_starts_with($realPath, $stagingDirectory . DIRECTORY_SEPARATOR)
        ) {
            throw new RuntimeException('The artifact source returned a path outside the private staging directory.');
        }

        if (filesize($realPath) === false || filesize($realPath) < 1) {
            throw new RuntimeException('The artifact source returned an empty staged file.');
        }

        if (!chmod($realPath, 0600)) {
            throw new RuntimeException('Unable to set private permissions on the staged artifact.');
        }
    }

    /**
     * @return list<string>
     */
    private function cleanupDownloadDirectory(string $stagingDirectory): array
    {
        if (is_link($stagingDirectory)) {
            return ['Refused to clean a staging path that is a symbolic link.'];
        }

        if (!is_dir($stagingDirectory)) {
            return file_exists($stagingDirectory)
                ? ['The staging path is no longer a directory.']
                : [];
        }

        $failures = [];
        try {
            foreach (new \FilesystemIterator($stagingDirectory) as $entry) {
                if ($entry->isFile() || $entry->isLink()) {
                    $failure = $this->attemptCleanup(
                        static fn (string $path): bool => unlink($path),
                        $entry->getPathname(),
                        'remove staged data',
                    );
                    if ($failure !== null) {
                        $failures[] = $failure;
                    }
                }
            }
        } catch (Throwable $exception) {
            $failures[] = 'Unable to inspect the staging directory: ' . $exception->getMessage();
        }

        $failure = $this->attemptCleanup(
            static fn (string $directory): bool => rmdir($directory),
            $stagingDirectory,
            'remove the staging directory',
        );
        if ($failure !== null) {
            $failures[] = $failure;
        }

        return $failures;
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
                $path = $entry->getPathname();
                if ($entry->isLink() || $entry->isFile()) {
                    $failure = $this->attemptCleanup(
                        static fn (string $file): bool => unlink($file),
                        $path,
                        'remove inspected package data',
                    );
                } elseif ($entry->isDir()) {
                    $failure = $this->attemptCleanup(
                        static fn (string $directory): bool => rmdir($directory),
                        $path,
                        'remove an inspection directory',
                    );
                } else {
                    $failure = null;
                }
                if ($failure !== null) {
                    $failures[] = $failure;
                }
            }
        } catch (Throwable $exception) {
            $failures[] = 'Unable to inspect temporary package files: ' . $exception->getMessage();
        }

        $failure = $this->attemptCleanup(
            static fn (string $directory): bool => rmdir($directory),
            $inspectionDirectory,
            'remove the inspection directory',
        );
        if ($failure !== null) {
            $failures[] = $failure;
        }

        return $failures;
    }

    private function attemptCleanup(callable $operation, string $path, string $action): ?string
    {
        $warning = null;
        set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
            $warning = $message;

            return true;
        });

        try {
            $succeeded = $operation($path);
        } catch (Throwable $exception) {
            $succeeded = false;
            $warning = $exception->getMessage();
        } finally {
            restore_error_handler();
        }

        if ($succeeded) {
            return null;
        }

        return sprintf(
            'Unable to %s "%s"%s.',
            $action,
            $path,
            $warning === null ? '' : ': ' . $warning,
        );
    }
}
