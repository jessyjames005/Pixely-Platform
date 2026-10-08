<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for user engagement', function (): void {
    $this->getJson('/api/v1/me/favorites')->assertUnauthorized();
});

it('requires the user surface', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/me/favorites')
        ->assertSuccessful();
});

it('rejects malformed resource identifiers', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/me/favorites', [
            'resource_type' => '../users',
            'resource_id' => '1',
        ])
        ->assertUnprocessable();
});

it('does not trust a client supplied history timestamp', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/me/history', [
            'resource_type' => 'music.track',
            'resource_id' => '42',
            'action' => 'play',
            'occurred_at' => '2000-01-01T00:00:00Z',
        ])
        ->assertCreated()
        ->assertJsonMissing(['occurred_at' => '2000-01-01T00:00:00Z']);
});
