<?php

declare(strict_types=1);

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(TestCase::class);
uses(RefreshDatabase::class);

$jsonApiHeaders = ['Accept' => 'application/vnd.api+json'];

it('requires authentication to list CinemaMovie items', function () use ($jsonApiHeaders) {
    // Enable the cinema-movie extension before running this API feature test.
    $this->getJson('/api/v1/cinema-movie', $jsonApiHeaders)
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('returns an allowlisted JSON:API collection for CinemaMovie items', function () use ($jsonApiHeaders) {
    Permission::firstOrCreate(['name' => 'cinema-movie.items.view', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('cinema-movie.items.view');
    $this->actingAs($user);

    $this->getJson('/api/v1/cinema-movie', $jsonApiHeaders)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('jsonapi.version', '1.0');
});
