<?php

declare(strict_types=1);

namespace App\Core\Extensions\Database;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use App\Core\Extensions\Database\ExtensionMigrationCompatibilityChecker;
use App\Core\Versioning\KernelVersionProvider;

final class ExtensionMigrationRunner
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly Filesystem $files,
        private readonly ExtensionMigrationCompatibilityChecker $compatibilityChecker,
        private readonly KernelVersionProvider $kernelVersionProvider,
        private readonly ExtensionMigrationRepository $migrationRepository,
    ) {
    }

    public function migrate(string $extensionId): void
    {
        $path = $this->migrationPath($extensionId);

        if ($path === null) {
            return;
        }

        $manifest = $this->extensionManifest($extensionId);

        $this->compatibilityChecker->check(
            $manifest,
            $this->kernelVersionProvider->getVersion(),
        );

        $files = $this->migrator->getMigrationFiles($path);
        $ran = $this->migrationRepository->getRan($extensionId);
        $batch = $this->migrationRepository->getNextBatchNumber($extensionId);

        foreach ($files as $migrationName => $migrationFile) {
            if (in_array($migrationName, $ran, true)) {
                continue;
            }

            require_once $migrationFile;

            $className = $this->migrationClassName($migrationFile);

            if (! class_exists($className)) {
                throw new \RuntimeException(
                    sprintf('Migration class not found: %s', $className),
                );
            }

            /** @var \Illuminate\Database\Migrations\Migration $migration */
            $migration = new $className();

            $migration->up();

            $this->migrationRepository->record(
                $extensionId,
                $migrationName,
                $batch,
            );
        }
    }

    /**
     * Load the extension manifest used for migration compatibility checks.
     *
     * @return array<string, mixed>
     */
    private function extensionManifest(string $extensionId): array
    {
        $manifestPath = base_path(
            'app/Extensions/' . ucfirst($extensionId) . '/extension.php'
        );

        if (! $this->files->isFile($manifestPath)) {
            return [];
        }

        $manifest = require $manifestPath;

        return is_array($manifest) ? $manifest : [];
    }

    public function rollback(string $extensionId): void
    {
        $path = $this->migrationPath($extensionId);

        if ($path === null) {
            return;
        }

        $files = $this->migrator->getMigrationFiles($path);
        $ran = $this->migrationRepository->getRan($extensionId);

        $ranExtensionMigrations = array_values(
            array_filter(
                array_keys($files),
                static fn(string $migration): bool => in_array($migration, $ran, true),
            ),
        );

        if ($ranExtensionMigrations === []) {
            return;
        }

        $migrationName = end($ranExtensionMigrations);
        $migrationFile = $files[$migrationName];

        require_once $migrationFile;

        $className = $this->migrationClassName($migrationFile);

        if (! class_exists($className)) {
            throw new \RuntimeException(
                sprintf('Migration class not found: %s', $className),
            );
        }

        /** @var \Illuminate\Database\Migrations\Migration $migration */
        $migration = new $className();

        $migration->down();

        $this->migrationRepository->delete(
            $extensionId,
            $migrationName,
        );
    }

    private function migrationClassName(string $migrationFile): string
    {
        $filename = pathinfo($migrationFile, PATHINFO_FILENAME);

        $parts = explode('_', $filename);

        $className = implode('', array_map(
            static fn(string $part): string => ucfirst($part),
            array_slice($parts, 4),
        ));

        return $className;
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
        $ran = $this->migrationRepository->getRan($extensionId);

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
