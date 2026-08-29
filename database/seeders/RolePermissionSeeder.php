<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds the default administration roles and permissions.
 *
 * Naming convention: <domain>.<object>.<action>
 * - view / manage (create+update) / delete for CRUD objects
 * - explicit action names for non-CRUD tools (system.*)
 */
final class RolePermissionSeeder extends Seeder
{
    /**
     * Permissions managed by the administration.
     */
    private const PERMISSIONS = [
        // Gallery domain
        'gallery.photos.view',
        'gallery.photos.manage',
        'gallery.photos.delete',

        // Core domains
        'users.view',
        'users.manage',
        'users.delete',
        'roles.view',
        'roles.manage',
        'roles.delete',

        // Platform system tooling (non-CRUD, explicit actions)
        'system.logs.view',
        'system.cache.view',
        'system.cache.clear',
        'system.database.view',
        'system.sql.query',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
            );
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(self::PERMISSIONS);

        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editor->syncPermissions(['gallery.photos.view', 'gallery.photos.manage']);
    }
}
