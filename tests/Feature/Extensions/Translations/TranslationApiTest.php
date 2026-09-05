<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'translations.strings.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'translations.strings.manage', 'guard_name' => 'web']);
});

it('requires authentication to list translation modules', function () {
    $response = $this->getJson('/api/v1/translations/modules');

    $response->assertStatus(401);
});

it('requires translations.strings.view to list modules', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/translations/modules');

    $response->assertStatus(403);
});

it('lists translatable modules for a user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/translations/modules');

    $response
        ->assertOk()
        ->assertJsonFragment(['id' => 'core']);
});

it('lists groups for the core module', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/translations/core/groups?locale=en');

    $response
        ->assertOk()
        ->assertJsonFragment(['gallery']);
});

it('shows translation entries with completion for a group', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/translations/core/gallery?locale=fr&reference=en');

    $response
        ->assertOk()
        ->assertJsonPath('data.module', 'core')
        ->assertJsonPath('data.group', 'gallery')
        ->assertJsonStructure(['data' => ['entries', 'completion']]);
});

it('returns 404 for an unknown module', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->getJson('/api/v1/translations/does-not-exist/gallery');

    $response->assertNotFound();
});

it('requires translations.strings.manage (not just view) to update a group', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.view');
    $this->actingAs($user);

    $response = $this->putJson('/api/v1/translations/core/gallery', [
        'locale' => 'fr',
        'translations' => ['title' => 'Galerie'],
    ]);

    $response->assertStatus(403);
});

it('updates a translation group', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('translations.strings.manage');
    $this->actingAs($user);

    $response = $this->putJson('/api/v1/translations/core/gallery', [
        'locale' => 'fr',
        'translations' => ['title' => 'Galerie', 'upload' => 'Envoyer une photo'],
    ]);

    $response->assertOk();

    $show = $this->getJson('/api/v1/translations/core/gallery?locale=fr&reference=en');
    $byKey = collect($show->json('data.entries'))->keyBy('key');

    expect($byKey['upload']['target'])->toBe('Envoyer une photo');
});
