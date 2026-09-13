<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to view user settings', function () {
    $response = $this->getJson('/api/v1/settings/user');

    $response->assertStatus(401);
});

it('returns default user settings on first access', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/settings/user');

    $response
        ->assertOk()
        ->assertJsonPath('data.locale', null)
        ->assertJsonPath('data.theme', 'system')
        ->assertJsonPath('data.density', 'default')
        ->assertJsonPath('data.email_notifications', true);
});

it('updates the current user own locale preference', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->putJson('/api/v1/settings/user', [
        'locale' => 'fr',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.locale', 'fr');
});

it('updates the current user own theme, density and notification preferences', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->putJson('/api/v1/settings/user', [
        'theme' => 'dark',
        'density' => 'compact',
        'email_notifications' => false,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.theme', 'dark')
        ->assertJsonPath('data.density', 'compact')
        ->assertJsonPath('data.email_notifications', false);
});

it('rejects an unsupported theme', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->putJson('/api/v1/settings/user', [
        'theme' => 'neon',
    ]);

    $response->assertStatus(422);
});

it('rejects an unsupported density', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->putJson('/api/v1/settings/user', [
        'density' => 'ultra-wide',
    ]);

    $response->assertStatus(422);
});

it('backfills newly added preference keys onto an existing settings row', function () {
    $user = User::factory()->create();

    // Simulate a row saved before theme/density/email_notifications existed.
    App\Core\Settings\Models\UserSetting::query()->create([
        'user_id' => $user->id,
        'settings' => ['locale' => 'fr'],
    ]);

    $this->actingAs($user);
    $response = $this->getJson('/api/v1/settings/user');

    $response
        ->assertOk()
        ->assertJsonPath('data.locale', 'fr')
        ->assertJsonPath('data.theme', 'system')
        ->assertJsonPath('data.density', 'default')
        ->assertJsonPath('data.email_notifications', true);
});

it('scopes settings to the authenticated user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA);
    $this->putJson('/api/v1/settings/user', ['locale' => 'fr'])->assertOk();

    $this->actingAs($userB);
    $response = $this->getJson('/api/v1/settings/user');

    $response->assertJsonPath('data.locale', null);
});

it('rejects an unsupported locale', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->putJson('/api/v1/settings/user', [
        'locale' => 'zz',
    ]);

    $response->assertStatus(422);
});
