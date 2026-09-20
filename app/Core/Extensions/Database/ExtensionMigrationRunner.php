<?php

declare(strict_types=1);

namespace App\Core\Extensions\Database;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;

final class ExtensionMigrationRunner
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly Filesystem $files,
    ) {}

    public function migrate(string $extensionId): void
    {
        $path = $this->migrationPath($extensionId);

        if ($path === null) {
            return;
        }

        $this->migrator->run([$path], [
            'pretend' => false,
        ]);
    }

    public function rollback(string $extensionId): void
    {
        $path = $this->migrationPath($extensionId);

        if ($path === null) {
            return;
        }

        $this->migrator->rollback([$path], [
            'pretend' => false,
        ]);
    }

    /**
     * @return array<int, array{migration: string, status: string}>
     */
    public function status(string $extensionId): array
    {
        $path = $this->migrationPath($extensionId);

        if ($path === null) {
            return [];
        }

        $files = $this->migrator->getMigrationFiles($path);

        // Laravel stores executed migrations in the global migrations table.
        $ran = $this->migrator->getRepository()->getRan();

        return array_map(
            static fn(string $migration): array => [
                'migration' => $migration,
                'status' => in_array($migration, $ran, true) ? 'Ran' : 'Pending',
            ],
            array_keys($files),
        );
    }

    private function migrationPath(string $extensionId): ?string
    {
        $path = base_path(
            'app/Extensions/' . ucfirst($extensionId) . '/Database/Migrations'
        );

        return $this->files->isDirectory($path) ? $path : null;
    }
}
