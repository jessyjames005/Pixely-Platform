<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Database;

use App\Core\Extensions\Database\ExtensionMigrationCompatibilityChecker;
use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Core\Versioning\KernelVersionProvider;
use App\Core\Extensions\Database\ExtensionMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class ExtensionMigrationRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrate_checks_extension_kernel_compatibility(): void
    {
        $migrator = Mockery::mock(Migrator::class);
        $files = Mockery::mock(Filesystem::class);

        $extensionPath = base_path(
            'app/Extensions/Gallery/Database/Migrations'
        );

        $files
            ->shouldReceive('isDirectory')
            ->once()
            ->with($extensionPath)
            ->andReturn(true);

        $files
            ->shouldReceive('isFile')
            ->once()
            ->andReturn(true);

        $migrator
            ->shouldReceive('getMigrationFiles')
            ->once()
            ->with($extensionPath)
            ->andReturn([]);

        $migrationRepository = new ExtensionMigrationRepository();

        $runner = new ExtensionMigrationRunner(
            $migrator,
            $files,
            new ExtensionMigrationCompatibilityChecker(),
            new KernelVersionProvider(),
            $migrationRepository,
        );

        $this->artisan('migrate');

        $runner->migrate('Gallery');

        $this->addToAssertionCount(1);
    }
}
