<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds the default administration roles and permissions.
 */
final class RolePermissionSeeder extends Seeder
{
    /**
     * Permissions managed by the administration.
     */
    private const PERMISSIONS = [
        'users.view',
        'users.manage',
        'roles.view',
        'roles.manage',
        'gallery.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(self::PERMISSIONS);

        $editor = Role::firstOrCreate(['name' => 'editor']);
        $editor->syncPermissions(['gallery.manage']);
    }
}
