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
        // Core domains only — extension-owned permissions (e.g. Gallery)
        // are synced dynamically via ExtensionPermissionSynchronizer,
        // triggered on install/update/enable, or manually via
        // `php artisan pixely:sync-permissions`.
        'users.view',
        'users.manage',
        'users.delete',
        'roles.view',
        'roles.manage',
        'roles.delete',
        'system.logs.view',
        'system.cache.view',
        'system.cache.clear',
        'system.database.view',
        'system.sql.query',
        'system.extensions.view',
        'system.extensions.manage',
        'system.telescope.view',
        // system.extensions.install deliberately excluded — grant manually via /admin/roles if truly needed
    ];

    private const ADMIN_DEFAULT_PERMISSIONS = [
        'gallery.photos.view',
        'gallery.photos.manage',
        'gallery.photos.delete',
        'users.view',
        'users.manage',
        'users.delete',
        'roles.view',
        'roles.manage',
        'roles.delete',
        'system.logs.view',
        'system.cache.view',
        'system.cache.clear',
        'system.database.view',
        'system.sql.query',
        'system.extensions.view',
        'system.extensions.manage',
        // system.extensions.install deliberately excluded — grant manually via /admin/roles if truly needed
    ];

    public function run(): void
    {
        // Ensure extension-declared permissions (e.g. Gallery) exist
        // before roles reference them below.
        app(\Illuminate\Contracts\Console\Kernel::class)->call('pixely:sync-permissions');

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
            );
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([...self::PERMISSIONS, 'gallery.photos.view', 'gallery.photos.manage', 'gallery.photos.delete']);

        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editor->syncPermissions(['gallery.photos.view', 'gallery.photos.manage']);
    }
}
