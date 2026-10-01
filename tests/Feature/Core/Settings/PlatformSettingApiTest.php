<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
];

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'settings.platform.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'settings.platform.manage', 'guard_name' => 'web']);
});

it('requires authentication and view permission to fetch platform settings', function () use ($jsonApiHeaders) {
    $this->json('GET', '/api/v1/platform-settings/current', [], $jsonApiHeaders)
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);

    $this->actingAs(User::factory()->create());
    $this->json('GET', '/api/v1/platform-settings/current', [], $jsonApiHeaders)
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title']]]);
});

it('returns platform settings as a resource with only public attributes', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('settings.platform.view');
    $this->actingAs($user);

    $response = $this->json('GET', '/api/v1/platform-settings/current', [], $jsonApiHeaders);

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data.type', 'platform-settings')
        ->assertJsonPath('data.id', 'current')
        ->assertJsonPath('data.attributes.site_name', 'Pixely Platform')
        ->assertJsonPath('data.attributes.locale', 'en');

    expect(array_keys($response->json('data.attributes')))->toBe(['site_name', 'locale']);
});

it('updates platform settings through a JSON:API document and merges omitted attributes', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo(['settings.platform.view', 'settings.platform.manage']);
    $this->actingAs($user);

    $document = fn (array $attributes): array => [
        'data' => [
            'type' => 'platform-settings',
            'id' => 'current',
            'attributes' => $attributes,
        ],
    ];

    $this->json('PATCH', '/api/v1/platform-settings/current', $document(['site_name' => 'My Platform']), $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.attributes.site_name', 'My Platform');

    $this->json('PATCH', '/api/v1/platform-settings/current', $document(['locale' => 'fr']), $jsonApiHeaders)
        ->assertOk()
        ->assertJsonPath('data.type', 'platform-settings')
        ->assertJsonPath('data.id', 'current')
        ->assertJsonPath('data.attributes.site_name', 'My Platform')
        ->assertJsonPath('data.attributes.locale', 'fr');
});

it('requires manage permission and returns JSON:API validation errors', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('settings.platform.view');
    $this->actingAs($user);

    $this->json('PATCH', '/api/v1/platform-settings/current', [
        'data' => [
            'type' => 'platform-settings',
            'id' => 'current',
            'attributes' => ['site_name' => 'No permission'],
        ],
    ], $jsonApiHeaders)->assertForbidden();

    $user->givePermissionTo('settings.platform.manage');
    $this->json('PATCH', '/api/v1/platform-settings/current', [
        'data' => [
            'type' => 'platform-settings',
            'id' => 'current',
            'attributes' => ['locale' => 'zz'],
        ],
    ], $jsonApiHeaders)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonStructure(['errors' => [['status', 'title', 'detail', 'source' => ['pointer']]]])
        ->assertJsonPath('errors.0.source.pointer', '/data/attributes/locale');
});

it('enforces JSON:API media negotiation on settings routes', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('settings.platform.view');
    $this->actingAs($user);

    $this->json('GET', '/api/v1/platform-settings/current', [], ['Accept' => 'application/json'])
        ->assertNotAcceptable()
        ->assertHeader('Content-Type', 'application/vnd.api+json');
});
