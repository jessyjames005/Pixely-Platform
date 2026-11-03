<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Core\Extensions\Installer\ExtensionInstallerInterface;
use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use App\Core\Updates\Contracts\ArtifactSourceInterface;
use App\Core\Updates\Contracts\UpdateVerifierInterface;
use App\Core\Updates\Contracts\UpdateRegistryInterface;
use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\DTO\InstalledExtension;
use App\Core\Updates\DTO\UpdateCandidate;
use App\Core\Updates\Enums\ArtifactProvider;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;
use App\Core\Updates\Exceptions\UpdateVerificationException;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class DownloadExtensionReleaseCommandTest extends TestCase
{
    private string $testStoragePath;

    private string $stagingDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testStoragePath = storage_path(
            'framework/testing/extension-release-download-' . bin2hex(random_bytes(6)),
        );
        $this->app->useStoragePath($this->testStoragePath);
        $this->stagingDirectory = storage_path('app/extension-release-quarantine');
        config(['pixely.kernel_version' => '1.0.0']);
        $this->bindInventory([]);
        $verifier = $this->createMock(UpdateVerifierInterface::class);
        $verifier->method('verify');
        $this->app->instance(UpdateVerifierInterface::class, $verifier);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testStoragePath);

        parent::tearDown();
    }

    public function testDownloadsExactReleaseToPrivateQuarantineWithoutInstallingIt(): void
    {
        $this->bindInventory([$this->installedExtension('gallery', '1.0.0')]);
        $candidate = $this->candidate();
        $registry = $this->bindRegistry($candidate);
        $source = $this->bindArtifactSource();
        $verifier = $this->createMock(UpdateVerifierInterface::class);
        $verifier->expects(self::once())
            ->method('verify')
            ->with(
                self::callback(fn (string $path): bool => $path === $source->path
                    && is_file($path)
                    && file_get_contents($path) !== false
                    && str_starts_with(file_get_contents($path), "PK")),
                $candidate->artifact,
            );
        $this->app->instance(UpdateVerifierInterface::class, $verifier);
        $installer = $this->createMock(ExtensionInstallerInterface::class);
        $installer->expects(self::never())->method('install');
        $installer->expects(self::never())->method('update');
        $this->app->instance(ExtensionInstallerInterface::class, $installer);

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('Verified artifact remains quarantined at:')
            ->expectsOutputToContain('static package preflight succeeded; it is not installable')
            ->assertExitCode(0);

        self::assertSame(
            [UpdateTargetType::Extension, 'pixely/gallery', '1.2.0'],
            $registry->lookup,
        );
        self::assertSame(1, $source->calls);
        self::assertSame(0700, fileperms($this->stagingDirectory) & 0777);
        self::assertSame(0600, fileperms($source->path) & 0777);
        self::assertStringStartsWith($this->stagingDirectory . DIRECTORY_SEPARATOR, $source->path);
        self::assertSame(
            [basename($source->path)],
            array_values(array_map('basename', glob(dirname($source->path) . DIRECTORY_SEPARATOR . '*') ?: [])),
        );
    }

    public function testCleansUpTheStagedArtifactWhenVerificationFails(): void
    {
        $candidate = $this->candidate();
        $this->bindRegistry($candidate);
        $source = $this->bindArtifactSource();
        $verifier = $this->createMock(UpdateVerifierInterface::class);
        $verifier->expects(self::once())
            ->method('verify')
            ->with(self::anything(), $candidate->artifact)
            ->willThrowException(new UpdateVerificationException('Artifact signature verification failed.'));
        $this->app->instance(UpdateVerifierInterface::class, $verifier);

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('Artifact signature verification failed')
            ->assertExitCode(1);

        self::assertSame(1, $source->calls);
        self::assertFileDoesNotExist($source->path);
        self::assertDirectoryDoesNotExist(dirname($source->path));
        self::assertSame([], glob($this->stagingDirectory . DIRECTORY_SEPARATOR . '*') ?: []);
    }

    public function testRejectsInvalidPackageOrVersionInputWithoutRegistryAccess(): void
    {
        $registry = $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource();

        $this->artisan('pixely:extension:download ../gallery 1.2.0')
            ->assertExitCode(2);
        $this->artisan('pixely:extension:download pixely/gallery not-a-version')
            ->assertExitCode(2);

        self::assertNull($registry->lookup);
        self::assertSame(0, $source->calls);
    }

    public function testRejectsARegistryReleaseWithDifferentIdentity(): void
    {
        $registry = $this->bindRegistry($this->candidate(version: '1.2.1'));
        $source = $this->bindArtifactSource();

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('unexpected identity')
            ->assertExitCode(1);

        self::assertNotNull($registry->lookup);
        self::assertSame(0, $source->calls);
        self::assertDirectoryDoesNotExist($this->stagingDirectory);
    }

    public function testRejectsAReleaseWithTheWrongChannel(): void
    {
        $this->bindRegistry($this->candidate(channel: UpdateChannel::Beta));
        $source = $this->bindArtifactSource();

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0 --channel=stable')
            ->expectsOutputToContain('selected channel')
            ->assertExitCode(1);

        self::assertSame(0, $source->calls);
        self::assertDirectoryDoesNotExist($this->stagingDirectory);
    }

    public function testRejectsRuntimeIncompatibleReleases(): void
    {
        $this->bindRegistry($this->candidate(runtimeConstraints: [
            'php' => '>=999.0',
            'laravel' => '*',
            'pixely' => '*',
        ]));
        $source = $this->bindArtifactSource();

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('not compatible')
            ->assertExitCode(1);

        self::assertSame(0, $source->calls);
        self::assertDirectoryDoesNotExist($this->stagingDirectory);
    }

    public function testSurfacesRegistryErrors(): void
    {
        $this->bindRegistry(null, new \RuntimeException('Registry unavailable.'));
        $source = $this->bindArtifactSource();

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('Registry unavailable.')
            ->assertExitCode(1);

        self::assertSame(0, $source->calls);
    }

    public function testCleansUpAnInvalidReturnedStagedFile(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(empty: true);

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('empty staged file')
            ->assertExitCode(1);

        self::assertSame([], glob($this->stagingDirectory . DIRECTORY_SEPARATOR . '*') ?: []);
    }

    public function testCleansUpPartialFilesWhenTheArtifactSourceFails(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(throwsAfterWrite: true);

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('simulated transfer failure')
            ->assertExitCode(1);

        self::assertSame(1, $source->calls);
        self::assertSame([], glob($this->stagingDirectory . DIRECTORY_SEPARATOR . '*') ?: []);
    }

    public function testReportsCleanupFailureWithoutMaskingArtifactSourceFailure(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(
            throwsAfterWrite: true,
            createsResidualDirectory: true,
        );

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('Extension release download failed: simulated transfer failure')
            ->expectsOutputToContain('Cleanup failed: Unable to remove the staging directory')
            ->assertExitCode(1);

        self::assertFileDoesNotExist($source->path);
        self::assertDirectoryExists(dirname($source->path) . DIRECTORY_SEPARATOR . 'unexpected');
    }

    public function testRejectsManifestIdentityMismatchAndCleansTheQuarantine(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(manifest: $this->manifest(id: 'another', version: '1.2.0'));

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('does not match Registry package id')
            ->assertExitCode(1);

        self::assertDirectoryDoesNotExist(dirname($source->path));
        self::assertSame([], glob($this->stagingDirectory . DIRECTORY_SEPARATOR . '*') ?: []);
    }

    public function testRejectsManifestVersionMismatchAndCleansTheQuarantine(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(manifest: $this->manifest(version: '1.1.0'));
        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('does not match requested release')
            ->assertExitCode(1);
        self::assertDirectoryDoesNotExist(dirname($source->path));
    }

    public function testRejectsMissingManifest(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(
            entries: ['README.md' => 'no manifest'],
            includeManifest: false,
        );
        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('No extension.php manifest found')
            ->assertExitCode(1);
        self::assertDirectoryDoesNotExist(dirname($source->path));
    }

    public function testRejectsExecutableManifestExpressions(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(
            manifest: "<?php return ['id' => 'gallery', 'version' => getenv('BAD')];",
        );
        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('extension.php')
            ->assertExitCode(1);
        self::assertDirectoryDoesNotExist(dirname($source->path));
    }

    public function testRejectsIncompatibleMinimumKernelVersion(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(
            manifest: $this->manifest(minimumKernel: '2.0.0'),
        );
        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('requires Pixely kernel')
            ->assertExitCode(1);
        self::assertDirectoryDoesNotExist(dirname($source->path));
    }

    public function testRejectsMissingRequiredExtension(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(manifest: $this->manifest(requires: ['missing']));
        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('requires missing extension [missing]')
            ->assertExitCode(1);
        self::assertDirectoryDoesNotExist(dirname($source->path));
    }

    public function testRejectsInstalledConflictingExtension(): void
    {
        $this->bindInventory([$this->installedExtension('conflict')]);
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(
            manifest: $this->manifest(conflicts: ['conflict']),
        );
        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('conflicts with installed extension [conflict]')
            ->assertExitCode(1);
        self::assertDirectoryDoesNotExist(dirname($source->path));
    }

    public function testRejectsAnInstalledDependencyBelowItsRequiredMinimum(): void
    {
        $this->bindInventory([$this->installedExtension('files', '1.0.0')]);
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(
            manifest: $this->manifest(requires: ['files'], requireVersions: ['files' => '2.0.0']),
        );

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('requires [files] version [2.0.0] or newer')
            ->assertExitCode(1);
        self::assertDirectoryDoesNotExist(dirname($source->path));
    }

    public function testRejectsManifestEntrypointWithoutAStaticClassDeclaration(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(includeClassDeclaration: false);

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('has no static class declaration')
            ->assertExitCode(1);
        self::assertDirectoryDoesNotExist(dirname($source->path));
    }

    public function testRejectsZipSlipAndCleansTheDownloadedArchive(): void
    {
        $this->bindRegistry($this->candidate());
        $source = $this->bindArtifactSource(entries: ['../escape.txt' => 'outside']);

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('Unsafe path detected')
            ->assertExitCode(1);

        self::assertDirectoryDoesNotExist(dirname($source->path));
        self::assertFileDoesNotExist(dirname($source->path) . DIRECTORY_SEPARATOR . 'escape.txt');
    }

    private function bindRegistry(?UpdateCandidate $candidate, ?\Throwable $failure = null): object
    {
        $registry = new class ($candidate, $failure) implements UpdateRegistryInterface {
            public ?array $lookup = null;

            public function __construct(private ?UpdateCandidate $candidate, private ?\Throwable $failure)
            {
            }

            public function findUpdates(\App\Core\Updates\DTO\UpdateQuery $query): iterable
            {
                return [];
            }

            public function findRelease(
                UpdateTargetType $type,
                string $packageId,
                string $version,
            ): ?UpdateCandidate {
                $this->lookup = [$type, $packageId, $version];
                if ($this->failure !== null) {
                    throw $this->failure;
                }

                return $this->candidate;
            }
        };

        $this->app->instance(UpdateRegistryInterface::class, $registry);

        return $registry;
    }

    private function bindArtifactSource(
        bool $empty = false,
        bool $throwsAfterWrite = false,
        bool $createsResidualDirectory = false,
        ?string $manifest = null,
        array $entries = [],
        bool $includeManifest = true,
        bool $includeClassDeclaration = true,
    ): object {
        $manifest ??= $includeManifest ? $this->manifest() : null;
        $source = new class (
            $empty,
            $throwsAfterWrite,
            $createsResidualDirectory,
            $manifest,
            $entries,
            $includeClassDeclaration,
        ) implements ArtifactSourceInterface {
            public int $calls = 0;

            public string $path = '';

            public function __construct(
                private bool $empty,
                private bool $throwsAfterWrite,
                private bool $createsResidualDirectory,
                private ?string $manifest,
                private array $entries,
                private bool $includeClassDeclaration,
            ) {
            }

            public function download(ArtifactReference $artifact, string $stagingDirectory): string
            {
                $this->calls++;
                $this->path = $stagingDirectory . DIRECTORY_SEPARATOR
                    . 'artifact-' . bin2hex(random_bytes(5)) . '.zip';
                if ($this->empty) {
                    file_put_contents($this->path, '', LOCK_EX);
                } else {
                    $zip = new \ZipArchive();
                    $zip->open($this->path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                    if ($this->manifest !== null) {
                        $zip->addFromString('extension.php', $this->manifest);
                    }
                    if ($this->includeClassDeclaration) {
                        $zip->addFromString(
                            'GalleryExtension.php',
                            "<?php namespace App\\Extensions\\Gallery; final class GalleryExtension {}",
                        );
                    }
                    foreach ($this->entries as $name => $contents) {
                        $zip->addFromString($name, $contents);
                    }
                    $zip->close();
                }
                if ($this->throwsAfterWrite) {
                    if ($this->createsResidualDirectory) {
                        mkdir($stagingDirectory . DIRECTORY_SEPARATOR . 'unexpected', 0700);
                    }

                    throw new \RuntimeException('simulated transfer failure');
                }

                return $this->path;
            }
        };

        $this->app->instance(ArtifactSourceInterface::class, $source);

        return $source;
    }

    private function bindInventory(array $extensions): void
    {
        $inventory = new class ($extensions) implements InstalledExtensionInventoryInterface {
            public function __construct(private array $extensions)
            {
            }

            public function all(): array
            {
                return $this->extensions;
            }

            public function find(string $extensionId): ?InstalledExtension
            {
                foreach ($this->extensions as $extension) {
                    if ($extension->id === $extensionId) {
                        return $extension;
                    }
                }

                return null;
            }
        };
        $this->app->instance(InstalledExtensionInventoryInterface::class, $inventory);
    }

    private function installedExtension(string $id, string $version = '1.0.0'): InstalledExtension
    {
        return new InstalledExtension(
            id: $id,
            packageId: 'pixely/' . $id,
            name: ucfirst($id),
            version: $version,
            minimumKernelVersion: '1.0.0',
            path: storage_path('framework/testing/' . $id),
        );
    }

    private function manifest(
        string $id = 'gallery',
        string $version = '1.2.0',
        string $minimumKernel = '1.0.0',
        array $requires = [],
        array $conflicts = [],
        array $requireVersions = [],
    ): string {
        $values = [
            'id' => $id,
            'name' => ucfirst($id),
            'version' => $version,
            'class' => 'App\\Extensions\\Gallery\\GalleryExtension::class',
            'minimum_kernel_version' => $minimumKernel,
            'requires' => $requires,
            'require_versions' => $requireVersions,
            'conflicts' => $conflicts,
        ];
        $format = static function (mixed $value) use (&$format): string {
            if (!is_array($value)) {
                return var_export($value, true);
            }

            $items = [];
            foreach ($value as $key => $item) {
                $items[] = (is_string($key) ? var_export($key, true) . ' => ' : '')
                    . $format($item);
            }

            return '[' . implode(', ', $items) . ']';
        };
        $code = "<?php\ndeclare(strict_types=1);\nreturn [\n";
        foreach ($values as $key => $value) {
            if ($key === 'class') {
                $code .= "    'class' => App\\Extensions\\Gallery\\GalleryExtension::class,\n";
            } else {
                $code .= '    ' . var_export($key, true) . ' => ' . $format($value) . ",\n";
            }
        }

        return $code . "];\n";
    }

    /** @param array<string, string> $runtimeConstraints */
    private function candidate(
        string $version = '1.2.0',
        UpdateChannel $channel = UpdateChannel::Stable,
        array $runtimeConstraints = ['php' => '*', 'laravel' => '*', 'pixely' => '*'],
    ): UpdateCandidate {
        return new UpdateCandidate(
            UpdateTargetType::Extension,
            'pixely/gallery',
            'Gallery',
            $version,
            $channel,
            new ArtifactReference(
                ArtifactProvider::Pixely,
                'https://updates.pixely.test/artifacts/gallery.zip',
                str_repeat('a', 64),
                expectedSize: strlen('payload-not-yet-verified'),
            ),
            runtimeConstraints: $runtimeConstraints,
        );
    }
}
