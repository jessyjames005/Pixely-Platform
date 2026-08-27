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
        ->assertJsonPath('data.locale', null);
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
