<?php

declare(strict_types=1);

use App\Core\Users\Models\UserFavorite;
use App\Core\Users\Models\UserHistoryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('protects favorites and history endpoints with authentication', function () {
    $this->getJson('/api/v1/me/favorites')->assertUnauthorized();
    $this->getJson('/api/v1/me/history')->assertUnauthorized();
});

it('allows a user to manage only their own favorites and history', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson('/api/v1/me/favorites', [
        'resource_type' => 'gallery.photo',
        'resource_id' => '42',
        'metadata' => ['title' => 'Sunset'],
    ])->assertCreated()->assertJsonPath('resource_type', 'gallery.photo');

    $this->postJson('/api/v1/me/history', [
        'resource_type' => 'gallery.photo',
        'resource_id' => '42',
        'action' => 'view',
    ])->assertCreated()->assertJsonPath('resource_id', '42');

    $this->getJson('/api/v1/me/favorites')
        ->assertOk()
        ->assertJsonPath('data.0.resource_id', '42');

    $this->getJson('/api/v1/me/history')
        ->assertOk()
        ->assertJsonPath('data.0.resource_type', 'gallery.photo');

    $this->deleteJson('/api/v1/me/favorites/gallery.photo/42')
        ->assertOk()
        ->assertJsonPath('removed', true);

    expect(UserFavorite::query()->where('user_id', $user->id)->exists())->toBeFalse();
    expect(UserHistoryEntry::query()->where('user_id', $user->id)->exists())->toBeTrue();
});
