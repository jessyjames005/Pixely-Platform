<?php

declare(strict_types=1);

namespace App\Core\Extensions\Database;

use Illuminate\Support\Facades\DB;

final class ExtensionMigrationRepository
{
    private const TABLE = 'extension_migrations';

    /**
     * Return all migrations recorded for an extension.
     *
     * @return array<int, string>
     */
    public function getRan(string $extensionId): array
    {
        return DB::table(self::TABLE)
            ->where('extension_id', $extensionId)
            ->orderBy('id')
            ->pluck('migration')
            ->all();
    }

    /**
     * Record an executed extension migration.
     */
    public function record(
        string $extensionId,
        string $migration,
        int $batch,
    ): void {
        DB::table(self::TABLE)->insert([
            'extension_id' => $extensionId,
            'migration' => $migration,
            'batch' => $batch,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Remove a recorded extension migration.
     */
    public function delete(
        string $extensionId,
        string $migration,
    ): void {
        DB::table(self::TABLE)
            ->where('extension_id', $extensionId)
            ->where('migration', $migration)
            ->delete();
    }

    /**
     * Return the batch assigned to an extension migration.
     */
    public function getBatch(
        string $extensionId,
        string $migration,
    ): ?int {
        $batch = DB::table(self::TABLE)
            ->where('extension_id', $extensionId)
            ->where('migration', $migration)
            ->value('batch');

        return $batch === null ? null : (int) $batch;
    }

    /**
     * Return the next batch number for an extension.
     */
    public function getNextBatchNumber(string $extensionId): int
    {
        $batch = DB::table(self::TABLE)
            ->where('extension_id', $extensionId)
            ->max('batch');

        return ((int) $batch) + 1;
    }
}
