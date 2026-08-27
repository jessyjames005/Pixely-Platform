<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to list users', function () {
    $response = $this->getJson('/api/v1/users');

    $response->assertStatus(401);
});

it('lists users when authenticated', function () {
    $this->actingAs(User::factory()->create());

    User::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/users');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('creates a user', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Jane Doe')
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonMissingPath('data.password');

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
    ]);
});

it('rejects a duplicate email on creation', function () {
    $this->actingAs(User::factory()->create());

    User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
});

it('updates a user without changing the password', function () {
    $this->actingAs(User::factory()->create());

    $user = User::factory()->create(['name' => 'Old Name']);

    $response = $this->putJson("/api/v1/users/{$user->id}", [
        'name' => 'New Name',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    expect($user->refresh()->name)->toBe('New Name');
});

it('deletes a user', function () {
    $this->actingAs(User::factory()->create());

    $user = User::factory()->create();

    $response = $this->deleteJson("/api/v1/users/{$user->id}");

    $response->assertNoContent();

    expect(User::find($user->id))->toBeNull();
});

it('prevents a user from deleting their own account', function () {
    $currentUser = User::factory()->create();
    $this->actingAs($currentUser);

    $response = $this->deleteJson("/api/v1/users/{$currentUser->id}");

    $response->assertStatus(422);

    expect(User::find($currentUser->id))->not->toBeNull();
});
