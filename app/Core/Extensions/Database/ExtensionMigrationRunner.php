<?php

declare(strict_types=1);

namespace App\Core\Extensions\Database;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class ExtensionMigrationRunner
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly Filesystem $files,
    ) {
    }

    /**
     * Run pending migrations for one extension.
     */
    public function migrate(string $extensionPath, ?Command $output = null): void
    {
        $path = $this->migrationPath($extensionPath);

        if (! $this->files->isDirectory($path)) {
            return;
        }

        $this->migrator->run([$path], [
            'pretend' => false,
        ]);

        $output?->info('Extension migrations completed.');
    }

    private function migrationPath(string $extensionPath): string
    {
        $path = base_path('app/Extensions/' . $extensionPath . '/Database/Migrations');

        if (! $this->files->isDirectory($path)) {
            throw new RuntimeException(
                "Migration directory not found for extension [{$extensionPath}].",
            );
        }

        return $path;
    }
}
