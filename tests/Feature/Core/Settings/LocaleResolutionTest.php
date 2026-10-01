<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'settings.platform.manage', 'guard_name' => 'web']);
});

$jsonApiHeaders = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
];

it('applies the platform default locale for a guest request', function () {
    $this->getJson('/api/v1/locales');

    expect(App::getLocale())->toBe('en');
});

it('applies the user locale preference over the platform default', function () use ($jsonApiHeaders) {
    $user = User::factory()->create();
    $user->givePermissionTo('settings.platform.manage');
    $this->actingAs($user);

    $this->json('PATCH', '/api/v1/platform-settings/current', [
        'data' => [
            'type' => 'platform-settings',
            'id' => 'current',
            'attributes' => ['locale' => 'fr'],
        ],
    ], $jsonApiHeaders)->assertOk();

    $this->json('PATCH', "/api/v1/user-settings/{$user->id}", [
        'data' => [
            'type' => 'user-settings',
            'id' => (string) $user->id,
            'attributes' => ['locale' => 'en'],
        ],
    ], $jsonApiHeaders)->assertOk();

    $this->json('GET', '/api/v1/locales', [], $jsonApiHeaders);

    expect(App::getLocale())->toBe('en');
});
