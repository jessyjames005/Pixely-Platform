<?php

declare(strict_types=1);

use App\Core\Settings\Models\UserSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
];

it('requires authentication to view user settings', function () use ($jsonApiHeaders) {
    $this->json('GET', '/api/v1/user-settings/1', [], $jsonApiHeaders)
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('returns default user settings as an identified resource on first access', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->json('GET', "/api/v1/user-settings/{$user->id}", [], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'user-settings')
        ->assertJsonPath('data.id', (string) $user->id)
        ->assertJsonPath('data.attributes.locale', null)
        ->assertJsonPath('data.attributes.theme', 'system')
        ->assertJsonPath('data.attributes.density', 'default')
        ->assertJsonPath('data.attributes.email_notifications', true);
});

it('updates only the authenticated user settings through JSON:API documents', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $document = fn (array $attributes): array => [
        'data' => [
            'type' => 'user-settings',
            'id' => (string) $user->id,
            'attributes' => $attributes,
        ],
    ];

    $this->json('PATCH', "/api/v1/user-settings/{$user->id}", $document(['locale' => 'fr']), $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'user-settings')
        ->assertJsonPath('data.attributes.locale', 'fr');

    $this->json('PATCH', "/api/v1/user-settings/{$user->id}", $document([
        'theme' => 'dark',
        'density' => 'compact',
        'email_notifications' => false,
    ]), $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.attributes.locale', 'fr')
        ->assertJsonPath('data.attributes.theme', 'dark')
        ->assertJsonPath('data.attributes.density', 'compact')
        ->assertJsonPath('data.attributes.email_notifications', false);

    $this->json('GET', "/api/v1/user-settings/{$otherUser->id}", [], $jsonApiHeaders)
        ->assertNotFound()
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('backfills old preference rows and rejects unsupported values', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    UserSetting::query()->create([
        'user_id' => $user->id,
        'settings' => ['locale' => 'fr'],
    ]);
    $this->actingAs($user);

    $this->json('GET', "/api/v1/user-settings/{$user->id}", [], $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.attributes.locale', 'fr')
        ->assertJsonPath('data.attributes.theme', 'system')
        ->assertJsonPath('data.attributes.density', 'default')
        ->assertJsonPath('data.attributes.email_notifications', true);

    $this->json('PATCH', "/api/v1/user-settings/{$user->id}", [
        'data' => [
            'type' => 'user-settings',
            'id' => (string) $user->id,
            'attributes' => ['theme' => 'neon'],
        ],
    ], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'source' => ['pointer']]]]);
});
