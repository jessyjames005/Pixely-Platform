<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to view platform settings', function () {
    $response = $this->getJson('/api/v1/settings/platform');

    $response->assertStatus(401);
});

it('returns default platform settings on first access', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->getJson('/api/v1/settings/platform');

    $response
        ->assertOk()
        ->assertJsonPath('data.site_name', 'Pixely Platform')
        ->assertJsonPath('data.locale', 'en');
});

it('updates platform settings and merges provided keys only', function () {
    $this->actingAs(User::factory()->create());

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
    $this->actingAs(User::factory()->create());

    $response = $this->putJson('/api/v1/settings/platform', [
        'locale' => 'zz',
    ]);

    $response->assertStatus(422);
});
