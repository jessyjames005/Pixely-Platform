<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Core\Extensions\Installer\ExtensionInstallerInterface;
use App\Core\Updates\Contracts\ArtifactSourceInterface;
use App\Core\Updates\Contracts\UpdateVerifierInterface;
use App\Core\Updates\Contracts\UpdateRegistryInterface;
use App\Core\Updates\DTO\ArtifactReference;
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
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testStoragePath);

        parent::tearDown();
    }

    public function testDownloadsExactReleaseToPrivateQuarantineWithoutInstallingIt(): void
    {
        $candidate = $this->candidate();
        $registry = $this->bindRegistry($candidate);
        $source = $this->bindArtifactSource();
        $verifier = $this->createMock(UpdateVerifierInterface::class);
        $verifier->expects(self::once())
            ->method('verify')
            ->with(
                self::callback(fn (string $path): bool => $path === $source->path
                    && is_file($path)
                    && file_get_contents($path) === 'payload-not-yet-verified'),
                $candidate->artifact,
            );
        $this->app->instance(UpdateVerifierInterface::class, $verifier);
        $installer = $this->createMock(ExtensionInstallerInterface::class);
        $installer->expects(self::never())->method('update');
        $this->app->instance(ExtensionInstallerInterface::class, $installer);

        $this->artisan('pixely:extension:download pixely/gallery 1.2.0')
            ->expectsOutputToContain('Verified artifact remains quarantined at:')
            ->expectsOutputToContain('verification succeeded; it is not installable')
            ->assertExitCode(0);

        self::assertSame(
            [UpdateTargetType::Extension, 'pixely/gallery', '1.2.0'],
            $registry->lookup,
        );
        self::assertSame(1, $source->calls);
        self::assertSame('payload-not-yet-verified', file_get_contents($source->path));
        self::assertSame(0700, fileperms($this->stagingDirectory) & 0777);
        self::assertSame(0600, fileperms($source->path) & 0777);
        self::assertStringStartsWith($this->stagingDirectory . DIRECTORY_SEPARATOR, $source->path);
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
    ): object {
        $source = new class ($empty, $throwsAfterWrite, $createsResidualDirectory) implements ArtifactSourceInterface {
            public int $calls = 0;

            public string $path = '';

            public function __construct(
                private bool $empty,
                private bool $throwsAfterWrite,
                private bool $createsResidualDirectory,
            ) {
            }

            public function download(ArtifactReference $artifact, string $stagingDirectory): string
            {
                $this->calls++;
                $this->path = $stagingDirectory . DIRECTORY_SEPARATOR
                    . 'artifact-' . bin2hex(random_bytes(5)) . '.part';
                file_put_contents($this->path, $this->empty ? '' : 'payload-not-yet-verified', LOCK_EX);
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
