<?php

declare(strict_types=1);

namespace App\Core\Extensions\Configuration;

use Illuminate\Support\Facades\DB;

/**
 * Persists extension configuration overrides in the database.
 */
final class DatabaseExtensionConfigurationRepository implements ExtensionConfigurationRepositoryInterface
{
    /**
     * Database table used to store extension configuration.
     */
    private const TABLE = 'extension_configurations';

    /**
     * Save configuration overrides for an extension.
     *
     * @param array<string, mixed> $configuration
     */
    public function save(
        string $extensionId,
        array $configuration,
    ): void {
        DB::table(self::TABLE)->updateOrInsert(
            [
                'extension_id' => $extensionId,
            ],
            [
                'configuration' => json_encode(
                    $configuration,
                    JSON_THROW_ON_ERROR,
                ),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    /**
     * Load configuration overrides for an extension.
     *
     * @return array<string, mixed>
     */
    public function load(string $extensionId): array
    {
        $row = DB::table(self::TABLE)
            ->where('extension_id', $extensionId)
            ->first();

        if ($row === null) {
            return [];
        }

        return json_decode(
            $row->configuration,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
