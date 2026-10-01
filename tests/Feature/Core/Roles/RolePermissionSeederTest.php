<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('marks seeded core permissions on fresh schemas and existing rows', function () {
    $permissionsTable = config('permission.table_names.permissions');

    expect(Schema::hasColumn($permissionsTable, 'is_core'))->toBeTrue();

    $existingPermission = Permission::create([
        'name' => 'users.view',
        'guard_name' => 'web',
    ]);

    expect((bool) $existingPermission->is_core)->toBeFalse();

    $this->seed(RolePermissionSeeder::class);

    expect((bool) Permission::where('name', 'users.view')->firstOrFail()->is_core)->toBeTrue();
});
