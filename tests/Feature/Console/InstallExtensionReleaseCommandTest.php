<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Core\Extensions\Audit\ExtensionAuditLog;
use App\Core\Extensions\Installer\ExtensionInstallerInterface;
use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use App\Core\Updates\Contracts\UpdateRegistryInterface;
use App\Core\Updates\Contracts\UpdateVerifierInterface;
use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\DTO\InstalledExtension;
use App\Core\Updates\DTO\UpdateCandidate;
use App\Core\Updates\Enums\ArtifactProvider;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

final class InstallExtensionReleaseCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $testStoragePath;

    private string $quarantineRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testStoragePath = storage_path(
            'framework/testing/extension-release-install-' . bin2hex(random_bytes(6)),
        );
        $this->app->useStoragePath($this->testStoragePath);
        $this->quarantineRoot = storage_path('app/extension-release-quarantine');
        config(['pixely.kernel_version' => '1.0.0']);
        $this->bindInventory([]);
        $this->bindRegistry($this->candidate());
        $this->bindVerifier();
        $this->bindInstaller();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testStoragePath);

        parent::tearDown();
    }

    public function testRejectsAnArtifactOutsideQuarantine(): void
    {
        $artifact = $this->createArtifact();
        $outsidePath = storage_path('app/outside.zip');
        self::assertTrue(copy($artifact, $outsidePath));

        $this->artisan($this->command($outsidePath))
            ->expectsOutputToContain('exactly one directory below')
            ->assertExitCode(1);

        self::assertFileExists($outsidePath);
    }

    public function testRejectsASymbolicLinkArtifact(): void
    {
        $target = $this->createArtifact();
        $link = dirname($target) . DIRECTORY_SEPARATOR . 'linked.zip';
        self::assertTrue(symlink($target, $link));

        $this->artisan($this->command($link))
            ->expectsOutputToContain('regular, non-symbolic-link')
            ->assertExitCode(1);

        self::assertFileExists($target);
    }

    public function testRejectsAMissingRegistryRelease(): void
    {
        $artifact = $this->createArtifact();
        $this->bindRegistry(null);
        $this->artisan($this->command($artifact))
            ->expectsOutputToContain('was not found in the Registry')
            ->assertExitCode(1);
    }

    public function testRejectsAnUnexpectedRegistryReleaseIdentity(): void
    {
        $artifact = $this->createArtifact();
        $this->bindRegistry($this->candidate(packageId: 'publisher/gallery'));
        $this->artisan($this->command($artifact))
            ->expectsOutputToContain('unexpected identity')
            ->assertExitCode(1);
    }

    public function testRejectsAnInstalledExtensionOwnedByAnotherRegistryPackage(): void
    {
        $this->bindInventory([
            $this->installedExtension(packageId: 'another-publisher/gallery'),
        ]);
        $artifact = $this->createArtifact();
        $verifier = $this->bindVerifier();
        $verifier->expects(self::never())->method('verify');

        $this->artisan($this->command($artifact))
            ->expectsOutputToContain('not [pixely/gallery]')
            ->assertExitCode(1);
    }

    public function testDoesNotCallInstallerWhenArtifactVerificationFails(): void
    {
        $artifact = $this->createArtifact();
        $verifier = $this->bindVerifier();
        $verifier->expects(self::once())
            ->method('verify')
            ->willThrowException(new RuntimeException('signature check failed'));
        $installer = $this->bindInstaller();
        $installer->expects(self::never())->method('install');
        $installer->expects(self::never())->method('update');

        $this->artisan($this->command($artifact, '--yes'))
            ->expectsOutputToContain('signature check failed')
            ->assertExitCode(1);
    }

    public function testDoesNotCallInstallerWhenStaticPreflightFails(): void
    {
        $artifact = $this->createArtifact(manifest: $this->manifest(id: 'different'));
        $installer = $this->bindInstaller();
        $installer->expects(self::never())->method('install');
        $installer->expects(self::never())->method('update');

        $this->artisan($this->command($artifact, '--yes'))
            ->expectsOutputToContain('does not match Registry package id')
            ->assertExitCode(1);

        self::assertSame(
            ['artifact.zip'],
            array_values(array_map('basename', glob(dirname($artifact) . DIRECTORY_SEPARATOR . '*') ?: [])),
        );
        self::assertDatabaseHas('extension_audit_logs', [
            'extension_id' => 'gallery',
            'action' => 'preflight',
            'version' => '1.2.0',
        ]);
        self::assertSame(
            1,
            ExtensionAuditLog::query()
                ->where('action', 'preflight')
                ->where('details->outcome', 'failure')
                ->where('details->stage', 'preflight')
                ->count(),
        );
    }

    public function testInstallsOnlyAfterInteractiveApprovalAndRetainsTheArtifact(): void
    {
        $artifact = $this->createArtifact();
        $verified = false;
        $verifier = $this->bindVerifier();
        $verifier->expects(self::exactly(2))
            ->method('verify')
            ->willReturnCallback(static function () use (&$verified): void {
                $verified = true;
            });
        $installer = $this->bindInstaller();
        $installer->expects(self::once())
            ->method('install')
            ->with($artifact, null)
            ->willReturnCallback(static function () use (&$verified): array {
                self::assertTrue($verified, 'The installer must not run before artifact verification.');

                return ['id' => 'gallery', 'name' => 'Gallery', 'version' => '1.2.0'];
            });
        $installer->expects(self::never())->method('update');

        $this->artisan($this->command($artifact))
            ->expectsConfirmation(
                'Proceed to install extension [gallery] version [1.2.0] from Registry package [pixely/gallery]?',
                'yes',
            )
            ->expectsOutputToContain('may execute publisher PHP')
            ->expectsOutputToContain('Verified artifact retained in quarantine:')
            ->assertExitCode(0);

        self::assertFileExists($artifact);
        self::assertSame(
            ['artifact.zip'],
            array_values(array_map('basename', glob(dirname($artifact) . DIRECTORY_SEPARATOR . '*') ?: [])),
        );
        self::assertDatabaseHas('extension_audit_logs', [
            'extension_id' => 'gallery',
            'action' => 'preflight',
            'version' => '1.2.0',
        ]);
    }

    public function testReplacesAnInstalledExtensionWithItsExactLocalIdAfterApproval(): void
    {
        $this->bindInventory([$this->installedExtension()]);
        $artifact = $this->createArtifact();
        $installer = $this->bindInstaller();
        $installer->expects(self::never())->method('install');
        $installer->expects(self::once())
            ->method('update')
            ->with($artifact, 'gallery', null)
            ->willReturn(['id' => 'gallery', 'name' => 'Gallery', 'version' => '1.2.0']);

        $this->artisan($this->command($artifact))
            ->expectsConfirmation(
                'Proceed to replace extension [gallery] version [1.2.0] from Registry package [pixely/gallery]?',
                'yes',
            )
            ->assertExitCode(0);
    }

    public function testDecliningConfirmationDoesNotCallInstallerOrChangeArtifact(): void
    {
        $artifact = $this->createArtifact();
        $installer = $this->bindInstaller();
        $installer->expects(self::never())->method('install');
        $installer->expects(self::never())->method('update');

        $this->artisan($this->command($artifact))
            ->expectsConfirmation(
                'Proceed to install extension [gallery] version [1.2.0] from Registry package [pixely/gallery]?',
                'no',
            )
            ->expectsOutputToContain('Installation cancelled')
            ->assertExitCode(0);

        self::assertFileExists($artifact);
    }

    public function testYesOptionApprovesInstallationInNonInteractiveMode(): void
    {
        $artifact = $this->createArtifact();
        $installer = $this->bindInstaller();
        $installer->expects(self::once())
            ->method('install')
            ->with($artifact, null)
            ->willReturn(['id' => 'gallery', 'name' => 'Gallery', 'version' => '1.2.0']);
        $installer->expects(self::never())->method('update');

        $this->artisan($this->command($artifact, '--yes --no-interaction'))
            ->assertExitCode(0);
    }

    public function testNonInteractiveRunWithoutYesIsRejectedWithoutCallingInstaller(): void
    {
        $artifact = $this->createArtifact();
        $installer = $this->bindInstaller();
        $installer->expects(self::never())->method('install');
        $installer->expects(self::never())->method('update');

        $this->artisan($this->command($artifact, '--no-interaction'))
            ->expectsOutputToContain('Use --yes to explicitly approve')
            ->assertExitCode(1);

        self::assertSame(
            1,
            ExtensionAuditLog::query()
                ->where('extension_id', 'gallery')
                ->where('action', 'install')
                ->where('version', '1.2.0')
                ->where('details->outcome', 'failure')
                ->where('details->stage', 'approval')
                ->count(),
        );
    }

    private function bindRegistry(?UpdateCandidate $candidate): void
    {
        $registry = new class ($candidate) implements UpdateRegistryInterface {
            public function __construct(private ?UpdateCandidate $candidate)
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
                return $this->candidate;
            }
        };
        $this->app->instance(UpdateRegistryInterface::class, $registry);
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

    private function bindVerifier(): \PHPUnit\Framework\MockObject\MockObject
    {
        $verifier = $this->createMock(UpdateVerifierInterface::class);
        $verifier->method('verify');
        $this->app->instance(UpdateVerifierInterface::class, $verifier);

        return $verifier;
    }

    private function bindInstaller(): \PHPUnit\Framework\MockObject\MockObject
    {
        $installer = $this->createMock(ExtensionInstallerInterface::class);
        $this->app->instance(ExtensionInstallerInterface::class, $installer);

        return $installer;
    }

    private function createArtifact(?string $manifest = null): string
    {
        $directory = $this->quarantineRoot . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16));
        self::assertTrue(mkdir($directory, 0700, true));

        $path = $directory . DIRECTORY_SEPARATOR . 'artifact.zip';
        $zip = new \ZipArchive();
        self::assertSame(true, $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $zip->addFromString('extension.php', $manifest ?? $this->manifest());
        $zip->addFromString(
            'GalleryExtension.php',
            "<?php namespace App\\Extensions\\Gallery; final class GalleryExtension {}",
        );
        self::assertTrue($zip->close());
        chmod($path, 0600);

        return $path;
    }

    private function command(string $artifact, string $options = ''): string
    {
        return 'pixely:extension:install pixely/gallery 1.2.0 ' . escapeshellarg($artifact)
            . ($options === '' ? '' : ' ' . $options);
    }

    private function manifest(string $id = 'gallery'): string
    {
        return "<?php\n"
            . "declare(strict_types=1);\n"
            . "return [\n"
            . "    'id' => '{$id}',\n"
            . "    'name' => 'Gallery',\n"
            . "    'version' => '1.2.0',\n"
            . "    'class' => App\\Extensions\\Gallery\\GalleryExtension::class,\n"
            . "];\n";
    }

    private function candidate(
        string $packageId = 'pixely/gallery',
        string $version = '1.2.0',
    ): UpdateCandidate {
        return new UpdateCandidate(
            UpdateTargetType::Extension,
            $packageId,
            'Gallery',
            $version,
            UpdateChannel::Stable,
            new ArtifactReference(
                ArtifactProvider::Pixely,
                'https://updates.pixely.test/artifacts/gallery.zip',
                str_repeat('a', 64),
                expectedSize: 1,
            ),
            runtimeConstraints: ['php' => '*', 'laravel' => '*', 'pixely' => '*'],
        );
    }

    private function installedExtension(
        string $packageId = 'pixely/gallery',
    ): InstalledExtension {
        return new InstalledExtension(
            id: 'gallery',
            packageId: $packageId,
            name: 'Gallery',
            version: '1.0.0',
            minimumKernelVersion: '1.0.0',
            path: storage_path('framework/testing/gallery'),
        );
    }
}
