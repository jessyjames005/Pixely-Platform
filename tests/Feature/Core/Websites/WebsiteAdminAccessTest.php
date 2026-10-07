<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires the website pages permission', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->getJson('/api/v1/website/pages')->assertForbidden();
});

it('allows website page access to an authorized administrator', function (): void {
    $user = User::factory()->create();
    $permission = Permission::findOrCreate('website.pages.view', 'web');
    $user->givePermissionTo($permission);

    $this->actingAs($user);

    $this->getJson('/api/v1/website/pages')->assertOk();
});
