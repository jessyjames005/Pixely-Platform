<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Installer;

use App\Core\Extensions\Installer\ExtensionInstallerFilesystem;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class ExtensionInstallerFilesystemTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/installer-rollback-' . uniqid('', true));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            (new Filesystem())->deleteDirectory($this->root);
        }

        parent::tearDown();
    }

    public function test_it_restores_the_original_extension_and_cleans_staging_after_a_failure(): void
    {
        $target = $this->root . '/Demo';
        $backup = $this->root . '/Demo.backup';
        $staging = $this->root . '/staging';
        $this->makeDirectoryWithFile($target, 'candidate');
        $this->makeDirectoryWithFile($backup, 'original');
        $this->makeDirectoryWithFile($staging, 'package');

        $refreshed = false;
        $errors = (new ExtensionInstallerFilesystem())->rollback(
            $target,
            $backup,
            true,
            $staging,
            function () use (&$refreshed): void {
                $refreshed = true;
            },
        );

        self::assertSame([], $errors['errors']);
        self::assertFalse($errors['filesystem_may_be_inconsistent']);
        self::assertSame('original', file_get_contents($target . '/contents.txt'));
        self::assertDirectoryDoesNotExist($backup);
        self::assertDirectoryDoesNotExist($staging);
        self::assertTrue($refreshed);
    }

    public function test_it_reports_a_failed_restore_and_preserves_the_backup_path(): void
    {
        $target = $this->root . '/Demo';
        $backup = $this->root . '/Demo.backup';
        $staging = $this->root . '/staging';
        $this->makeDirectoryWithFile($target, 'candidate');
        $this->makeDirectoryWithFile($backup, 'original');
        $this->makeDirectoryWithFile($staging, 'package');

        File::shouldReceive('deleteDirectory')
            ->twice()
            ->andReturnUsing(fn (string $path): bool => (new Filesystem())->deleteDirectory($path));
        File::shouldReceive('moveDirectory')
            ->once()
            ->with($backup, $target)
            ->andReturnFalse();

        $errors = (new ExtensionInstallerFilesystem())->rollback(
            $target,
            $backup,
            true,
            $staging,
            static function (): void {
            },
        );

        self::assertCount(1, $errors['errors']);
        self::assertStringContainsString('Restoring the extension backup', $errors['errors'][0]);
        self::assertStringContainsString($backup, $errors['errors'][0]);
        self::assertTrue($errors['filesystem_may_be_inconsistent']);
        self::assertDirectoryDoesNotExist($target);
        self::assertDirectoryExists($backup);
        self::assertDirectoryDoesNotExist($staging);
    }

    public function test_it_removes_a_failed_fresh_install_and_refreshes_the_autoloader(): void
    {
        $target = $this->root . '/Demo';
        $staging = $this->root . '/staging';
        $this->makeDirectoryWithFile($target, 'candidate');
        $this->makeDirectoryWithFile($staging, 'package');
        $refreshed = false;

        $errors = (new ExtensionInstallerFilesystem())->rollback(
            $target,
            null,
            true,
            $staging,
            function () use (&$refreshed): void {
                $refreshed = true;
            },
        );

        self::assertSame([], $errors['errors']);
        self::assertFalse($errors['filesystem_may_be_inconsistent']);
        self::assertDirectoryDoesNotExist($target);
        self::assertDirectoryDoesNotExist($staging);
        self::assertTrue($refreshed);
    }

    public function test_it_reports_an_autoloader_refresh_failure_after_restoring_files(): void
    {
        $target = $this->root . '/Demo';
        $backup = $this->root . '/Demo.backup';
        $staging = $this->root . '/staging';
        $this->makeDirectoryWithFile($target, 'candidate');
        $this->makeDirectoryWithFile($backup, 'original');
        $this->makeDirectoryWithFile($staging, 'package');

        $errors = (new ExtensionInstallerFilesystem())->rollback(
            $target,
            $backup,
            true,
            $staging,
            static function (): void {
                throw new \RuntimeException('Composer autoload refresh failed.');
            },
        );

        self::assertCount(1, $errors['errors']);
        self::assertStringContainsString(
            'Refreshing the autoloader after filesystem rollback failed',
            $errors['errors'][0],
        );
        self::assertStringContainsString('Composer autoload refresh failed.', $errors['errors'][0]);
        self::assertFalse($errors['filesystem_may_be_inconsistent']);
        self::assertSame('original', file_get_contents($target . '/contents.txt'));
        self::assertDirectoryDoesNotExist($backup);
        self::assertDirectoryDoesNotExist($staging);
    }

    public function test_it_reports_staging_cleanup_failures(): void
    {
        $target = $this->root . '/Demo';
        $staging = $this->root . '/staging';
        $this->makeDirectoryWithFile($target, 'candidate');
        $this->makeDirectoryWithFile($staging, 'package');

        File::shouldReceive('deleteDirectory')
            ->once()
            ->with($target)
            ->andReturnUsing(fn (string $path): bool => (new Filesystem())->deleteDirectory($path));
        File::shouldReceive('deleteDirectory')
            ->once()
            ->with($staging)
            ->andReturnFalse();

        $errors = (new ExtensionInstallerFilesystem())->rollback(
            $target,
            null,
            true,
            $staging,
            static function (): void {
            },
        );

        self::assertCount(1, $errors['errors']);
        self::assertStringContainsString('Removing extension staging files', $errors['errors'][0]);
        self::assertStringContainsString($staging, $errors['errors'][0]);
        self::assertFalse($errors['filesystem_may_be_inconsistent']);
        self::assertDirectoryDoesNotExist($target);
        self::assertDirectoryExists($staging);
    }

    public function test_it_preserves_the_primary_failure_and_reports_filesystem_and_database_recovery_details(): void
    {
        $backup = $this->root . '/Demo.backup';
        $this->makeDirectoryWithFile($backup, 'original');
        $primaryFailure = new \RuntimeException('Migration failed at version 2.');

        $failure = (new ExtensionInstallerFilesystem())->failure(
            $primaryFailure,
            ["Restoring the extension backup failed for [{$backup}]."],
            $backup,
            true,
            false,
            'demo',
            true,
        );

        self::assertSame($primaryFailure, $failure->getPrevious());
        self::assertStringContainsString('Migration failed at version 2.', $failure->getMessage());
        self::assertStringContainsString('inconsistent filesystem state', $failure->getMessage());
        self::assertStringContainsString("backup remains at [{$backup}]", $failure->getMessage());
        self::assertStringContainsString('may have partially applied', $failure->getMessage());
        self::assertStringContainsString('not automatically rolled back', $failure->getMessage());
    }

    private function makeDirectoryWithFile(string $directory, string $contents): void
    {
        mkdir($directory, 0777, true);
        file_put_contents($directory . '/contents.txt', $contents);
    }
}
