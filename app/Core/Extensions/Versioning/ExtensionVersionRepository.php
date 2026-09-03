<?php

declare(strict_types=1);

namespace App\Core\Extensions\Versioning;

use Illuminate\Support\Facades\DB;

/**
 * Tracks the logical "installed version" of each extension,
 * independent of the manifest version shipped in its files and
 * independent of the raw Laravel migrations table.
 */
final class ExtensionVersionRepository
{
    /**
     * Returns the installed version, or null if never tracked
     * (e.g. an extension installed before this mechanism existed).
     */
    public function find(string $extensionId): ?string
    {
        return DB::table('extension_installed_versions')
            ->where('extension_id', $extensionId)
            ->value('version');
    }

    public function set(string $extensionId, string $version): void
    {
        DB::table('extension_installed_versions')->updateOrInsert(
            ['extension_id' => $extensionId],
            ['version' => $version, 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
