<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'settings.platform.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'settings.platform.manage', 'guard_name' => 'web']);
});

it('requires authentication to view platform settings', function () {
    $response = $this->getJson('/api/v1/settings/platform');

    $response->assertStatus(401);
});

it('requires settings.platform.view to view platform settings', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/settings/platform');

    $response->assertStatus(403);
});

it('returns default platform settings for a user with view permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('settings.platform.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/settings/platform');

    $response
        ->assertOk()
        ->assertJsonPath('data.site_name', 'Pixely Platform')
        ->assertJsonPath('data.locale', 'en');
});

it('requires settings.platform.manage (not just view) to update platform settings', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('settings.platform.view');
    $this->actingAs($user);

    $response = $this->putJson('/api/v1/settings/platform', [
        'site_name' => 'My Platform',
    ]);

    $response->assertStatus(403);
});

it('updates platform settings and merges provided keys only', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['settings.platform.view', 'settings.platform.manage']);
    $this->actingAs($user);

    $this->putJson('/api/v1/settings/platform', [
        'site_name' => 'My Platform',
    ])->assertOk();

    $response = $this->putJson('/api/v1/settings/platform', [
        'locale' => 'fr',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.site_name', 'My Platform')
        ->assertJsonPath('data.locale', 'fr');
});

it('rejects an unsupported locale', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('settings.platform.manage');
    $this->actingAs($user);

    $response = $this->putJson('/api/v1/settings/platform', [
        'locale' => 'zz',
    ]);

    $response->assertStatus(422);
});
