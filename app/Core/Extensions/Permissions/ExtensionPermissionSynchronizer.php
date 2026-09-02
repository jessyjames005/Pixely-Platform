<?php

declare(strict_types=1);

namespace App\Core\Extensions\Permissions;

use App\Core\Extensions\Contracts\ExtensionInterface;
use Spatie\Permission\Models\Permission;

/**
 * Creates any permission an extension declares that doesn't exist yet.
 *
 * Deliberately additive-only: never deletes a permission on disable
 * or uninstall, since that would silently break existing role
 * assignments referencing it. Removing a stale permission is a
 * separate, explicit administrative action (not yet built — see
 * ROADMAP.md).
 */
final class ExtensionPermissionSynchronizer
{
    /**
     * @return array<int, string> the permission names that were newly created
     */
    public function sync(ExtensionInterface $extension): array
    {
        if (! $extension instanceof ExtensionPermissionsInterface) {
            return [];
        }

        $created = [];

        foreach ($extension->declaredPermissions() as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
            );

            if ($permission->wasRecentlyCreated) {
                $created[] = $permissionName;
            }
        }

        return $created;
    }
}
