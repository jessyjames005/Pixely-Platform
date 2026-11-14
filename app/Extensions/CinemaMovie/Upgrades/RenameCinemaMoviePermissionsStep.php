<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Upgrades;

use App\Core\Extensions\Versioning\ExtensionUpgradeStepInterface;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Move the scaffold's generic item permissions to the movie domain,
 * retaining any role and direct-user grants made before the rename.
 */
final class RenameCinemaMoviePermissionsStep implements ExtensionUpgradeStepInterface
{
    /**
     * @var array<string, string>
     */
    private const PERMISSIONS = [
        'cinema-movie.items.view' => 'cinema-movie.movies.view',
        'cinema-movie.items.manage' => 'cinema-movie.movies.manage',
        'cinema-movie.items.delete' => 'cinema-movie.movies.delete',
    ];

    public function version(): string
    {
        return '1.1.0';
    }

    public function description(): string
    {
        return 'Rename CinemaMovie item permissions to movie permissions while preserving assignments.';
    }

    public function apply(): void
    {
        $tables = config('permission.table_names');
        $permissionPivot = config('permission.column_names.permission_pivot_key') ?: 'permission_id';

        foreach (self::PERMISSIONS as $legacyName => $currentName) {
            $legacy = Permission::query()
                ->where('name', $legacyName)
                ->where('guard_name', 'web')
                ->first();

            if ($legacy === null) {
                continue;
            }

            $current = Permission::query()->firstOrCreate([
                'name' => $currentName,
                'guard_name' => 'web',
            ]);

            $this->copyAssignments($tables['role_has_permissions'], $permissionPivot, $legacy->id, $current->id);
            $this->copyAssignments($tables['model_has_permissions'], $permissionPivot, $legacy->id, $current->id);

            $legacy->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function copyAssignments(string $table, string $permissionColumn, int $legacyId, int $currentId): void
    {
        DB::table($table)
            ->where($permissionColumn, $legacyId)
            ->get()
            ->each(function (object $assignment) use ($table, $permissionColumn, $currentId): void {
                $attributes = (array) $assignment;
                $attributes[$permissionColumn] = $currentId;

                DB::table($table)->insertOrIgnore($attributes);
            });
    }
}
