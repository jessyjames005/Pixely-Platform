<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Core\Extensions\Installer\ExtensionInstallerInterface;
use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use App\Core\Updates\Contracts\UpdateRegistryInterface;
use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\DTO\InstalledExtension;
use App\Core\Updates\DTO\UpdateCandidate;
use App\Core\Updates\DTO\UpdateQuery;
use App\Core\Updates\Enums\ArtifactProvider;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class ExtensionUpdatesCommandTest extends TestCase
{
    private string $testStoragePath;

    private string $reportsDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testStoragePath = storage_path(
            'framework/testing/extension-updates-command-' . bin2hex(random_bytes(6)),
        );
        $this->app->useStoragePath($this->testStoragePath);
        $this->reportsDirectory = storage_path('app/extension-update-reports');
        File::ensureDirectoryExists($this->reportsDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testStoragePath);

        parent::tearDown();
    }

    public function test_it_reports_a_compatible_candidate_without_installing_it_and_is_visible_in_history(): void
    {
        $this->bindInventory([$this->installed()]);
        $registry = $this->bindRegistry([$this->candidate('1.2.0')]);
        $installer = $this->createMock(ExtensionInstallerInterface::class);
        $installer->expects(self::never())->method('update');
        $this->app->instance(ExtensionInstallerInterface::class, $installer);
        $installedVersion = $this->installed()->version;

        $this->artisan('pixely:updates')
            ->expectsOutputToContain('No files were changed.')
            ->assertExitCode(0);

        self::assertSame(1, $registry->queries);
        self::assertSame(0, $registry->releaseLookups);
        self::assertSame($installedVersion, $this->installed()->version);

        $reportPath = $this->latestReportPath();
        $report = json_decode((string) file_get_contents($reportPath), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('pixely:updates', $report['command']);
        self::assertSame('dry-run', $report['mode']);
        self::assertSame(0, $report['summary']['failed']);
        self::assertSame('planned', $report['items'][0]['status']);
        self::assertSame('1.2.0', $report['items'][0]['to']);

        $this->artisan('pixely:update:reports --status=dry-run')
            ->expectsOutputToContain(basename($reportPath))
            ->assertExitCode(0);
    }

    public function test_it_reports_no_candidate_when_registry_releases_are_incompatible(): void
    {
        $this->bindInventory([$this->installed()]);
        $this->bindRegistry([$this->candidate('1.2.0', ['php' => '>=999.0'])]);

        $this->artisan('pixely:updates')
            ->expectsOutputToContain('gallery (1.0.0 → no update)')
            ->expectsOutputToContain('No compatible newer release')
            ->assertExitCode(0);

        $report = json_decode((string) file_get_contents($this->latestReportPath()), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('no-update', $report['items'][0]['status']);
        self::assertNull($report['items'][0]['to']);
    }

    public function test_it_surfaces_and_records_registry_failures_as_failures(): void
    {
        $this->bindInventory([$this->installed()]);
        $registry = $this->bindRegistry([], new \RuntimeException('Registry unavailable.'));

        $this->artisan('pixely:updates')
            ->expectsOutputToContain('Update discovery failed: Registry unavailable.')
            ->assertExitCode(1);

        self::assertSame(1, $registry->queries);
        $report = json_decode((string) file_get_contents($this->latestReportPath()), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $report['summary']['failed']);
        self::assertSame(1, $report['summary']['exit_code']);
        self::assertSame('failed', $report['items'][0]['status']);

        $this->artisan('pixely:update:reports --status=failed')
            ->expectsOutputToContain(basename($this->latestReportPath()))
            ->assertExitCode(0);
    }

    private function bindInventory(array $extensions): void
    {
        $this->app->instance(
            InstalledExtensionInventoryInterface::class,
            new class ($extensions) implements InstalledExtensionInventoryInterface {
                /**
                 * @param list<InstalledExtension> $extensions
                 */
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
            },
        );
    }

    private function bindRegistry(array $candidates, ?\Throwable $failure = null): object
    {
        $registry = new class ($candidates, $failure) implements UpdateRegistryInterface {
            public int $queries = 0;
            public int $releaseLookups = 0;

            public function __construct(private array $candidates, private ?\Throwable $failure)
            {
            }

            public function findUpdates(UpdateQuery $query): iterable
            {
                $this->queries++;
                if ($this->failure !== null) {
                    throw $this->failure;
                }

                return $this->candidates;
            }

            public function findRelease(UpdateTargetType $type, string $packageId, string $version): ?UpdateCandidate
            {
                $this->releaseLookups++;

                return null;
            }
        };

        $this->app->instance(UpdateRegistryInterface::class, $registry);

        return $registry;
    }

    private function installed(): InstalledExtension
    {
        return new InstalledExtension(
            'gallery',
            'pixely/gallery',
            'Gallery',
            '1.0.0',
            '1.0.0',
            '/extensions/Gallery',
        );
    }

    private function candidate(string $version, array $runtimeConstraints = []): UpdateCandidate
    {
        return new UpdateCandidate(
            UpdateTargetType::Extension,
            'pixely/gallery',
            'Gallery',
            $version,
            UpdateChannel::Stable,
            new ArtifactReference(
                ArtifactProvider::Pixely,
                'https://registry.pixely.test/artifacts/gallery.zip',
                str_repeat('a', 64),
            ),
            runtimeConstraints: array_replace([
                'php' => '*',
                'laravel' => '*',
                'pixely' => '*',
            ], $runtimeConstraints),
        );
    }

    private function latestReportPath(): string
    {
        $reports = glob($this->reportsDirectory . DIRECTORY_SEPARATOR . '*.json') ?: [];
        sort($reports, SORT_STRING);

        return (string) end($reports);
    }
}
