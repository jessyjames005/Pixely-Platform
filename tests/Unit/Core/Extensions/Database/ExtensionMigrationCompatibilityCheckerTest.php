<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Database;

use App\Core\Extensions\Database\ExtensionMigrationCompatibilityChecker;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExtensionMigrationCompatibilityCheckerTest extends TestCase
{
    public function test_compatible_kernel_version_is_accepted(): void
    {
        $checker = new ExtensionMigrationCompatibilityChecker();

        $checker->check([
            'id' => 'gallery',
            'minimum_kernel_version' => '1.0.0',
        ], '1.2.0');

        $this->addToAssertionCount(1);
    }

    public function test_exact_minimum_kernel_version_is_accepted(): void
    {
        $checker = new ExtensionMigrationCompatibilityChecker();

        $checker->check([
            'id' => 'gallery',
            'minimum_kernel_version' => '1.0.0',
        ], '1.0.0');

        $this->addToAssertionCount(1);
    }

    public function test_missing_minimum_kernel_version_is_accepted(): void
    {
        $checker = new ExtensionMigrationCompatibilityChecker();

        $checker->check([
            'id' => 'gallery',
        ], '1.0.0');

        $this->addToAssertionCount(1);
    }

    public function test_older_kernel_version_is_rejected(): void
    {
        $checker = new ExtensionMigrationCompatibilityChecker();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Extension "gallery" requires Pixely Kernel >= 1.2.0.'
        );

        $checker->check([
            'id' => 'gallery',
            'minimum_kernel_version' => '1.2.0',
        ], '1.1.0');
    }

    public function test_invalid_minimum_kernel_version_is_rejected(): void
    {
        $checker = new ExtensionMigrationCompatibilityChecker();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Invalid minimum_kernel_version for extension "gallery".'
        );

        $checker->check([
            'id' => 'gallery',
            'minimum_kernel_version' => 'invalid',
        ], '1.0.0');
    }
}
