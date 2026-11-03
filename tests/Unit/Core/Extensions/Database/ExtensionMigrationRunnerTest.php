<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Database;

use App\Core\Extensions\Database\ExtensionMigrationCompatibilityChecker;
use App\Core\Extensions\Database\ExtensionMigrationRepository;
use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Core\Versioning\KernelVersionProvider;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class ExtensionMigrationRunnerTest extends TestCase
{
    use RefreshDatabase;

    private ?string $generatedMigrationDirectory = null;

    private ?string $effectTable = null;

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

    public function test_migrate_runs_pending_migrations_and_skips_them_afterward(): void
    {
        $suffix = 'a' . bin2hex(random_bytes(6));
        $this->effectTable = 'extension_runner_effects_' . $suffix;

        Schema::create($this->effectTable, function (Blueprint $table): void {
            $table->string('migration');
        });

        [$migrationName, $migrationFile] = $this->createMigrationFile(
            'create_runner_test_effects_' . $suffix,
            sprintf(
                "\\Illuminate\\Support\\Facades\\DB::table('%s')->insert(['migration' => 'executed']);",
                $this->effectTable,
            ),
        );

        $runner = $this->makeRunner($migrationName, $migrationFile, 2);
        $repository = new ExtensionMigrationRepository();

        $runner->migrate('Gallery');

        $this->assertSame(
            1,
            DB::table($this->effectTable)->where('migration', 'executed')->count(),
        );
        $this->assertSame([$migrationName], $repository->getRan('Gallery'));
        $this->assertSame(
            1,
            DB::table('extension_migrations')
                ->where('extension_id', 'Gallery')
                ->where('migration', $migrationName)
                ->count(),
        );

        $runner->migrate('Gallery');

        $this->assertSame(
            1,
            DB::table($this->effectTable)->where('migration', 'executed')->count(),
        );
        $this->assertSame([$migrationName], $repository->getRan('Gallery'));
    }

    public function test_failed_migration_is_not_recorded(): void
    {
        $suffix = 'a' . bin2hex(random_bytes(6));
        [$migrationName, $migrationFile] = $this->createMigrationFile(
            'fail_runner_migration_' . $suffix,
            "throw new \\RuntimeException('Migration failed.');",
        );
        $runner = $this->makeRunner($migrationName, $migrationFile);
        $repository = new ExtensionMigrationRepository();

        try {
            $runner->migrate('Gallery');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Migration failed.', $exception->getMessage());
            $this->assertSame([], $repository->getRan('Gallery'));

            return;
        }

        $this->fail('The failed migration did not throw an exception.');
    }

    protected function tearDown(): void
    {
        if ($this->effectTable !== null) {
            Schema::dropIfExists($this->effectTable);
        }

        if ($this->generatedMigrationDirectory !== null) {
            (new Filesystem())->deleteDirectory(
                $this->generatedMigrationDirectory,
            );
        }

        parent::tearDown();
    }

    /**
     * @return array{string, string}
     */
    private function createMigrationFile(
        string $migrationSuffix,
        string $upStatements,
    ): array {
        $migrationName = '2026_11_03_230000_' . $migrationSuffix;
        $className = implode(
            '',
            array_map(
                'ucfirst',
                array_slice(explode('_', $migrationName), 4),
            ),
        );
        $this->generatedMigrationDirectory = base_path(
            'storage/framework/testing/extension-migration-runner-'
                . bin2hex(random_bytes(6)),
        );
        (new Filesystem())->makeDirectory(
            $this->generatedMigrationDirectory,
            0755,
            true,
        );

        $migrationFile = $this->generatedMigrationDirectory
            . DIRECTORY_SEPARATOR
            . $migrationName
            . '.php';
        $contents = "<?php\n\n"
            . "class {$className} extends \\Illuminate\\Database\\Migrations\\Migration\n"
            . "{\n"
            . "    public function up(): void\n"
            . "    {\n"
            . "        {$upStatements}\n"
            . "    }\n\n"
            . "    public function down(): void\n"
            . "    {\n"
            . "    }\n"
            . "}\n";

        $this->assertNotFalse(file_put_contents($migrationFile, $contents));

        return [$migrationName, $migrationFile];
    }

    private function makeRunner(
        string $migrationName,
        string $migrationFile,
        int $runs = 1,
    ): ExtensionMigrationRunner {
        $extensionPath = base_path(
            'app/Extensions/Gallery/Database/Migrations',
        );
        $manifestPath = base_path('app/Extensions/Gallery/extension.php');
        $migrator = Mockery::mock(Migrator::class);
        $migrator
            ->shouldReceive('getMigrationFiles')
            ->times($runs)
            ->with($extensionPath)
            ->andReturn([$migrationName => $migrationFile]);

        $files = Mockery::mock(Filesystem::class);
        $files
            ->shouldReceive('isDirectory')
            ->times($runs)
            ->with($extensionPath)
            ->andReturn(true);
        $files
            ->shouldReceive('isFile')
            ->times($runs)
            ->with($manifestPath)
            ->andReturn(false);

        return new ExtensionMigrationRunner(
            $migrator,
            $files,
            new ExtensionMigrationCompatibilityChecker(),
            new KernelVersionProvider(),
            new ExtensionMigrationRepository(),
        );
    }
}
