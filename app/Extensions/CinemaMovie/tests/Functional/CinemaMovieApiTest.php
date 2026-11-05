<?php

declare(strict_types=1);

use App\Extensions\CinemaMovie\CinemaMovieExtension;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\Extensions\ExtensionApiTestHelper;

uses(TestCase::class);
uses(RefreshDatabase::class);

it('requires authentication to list CinemaMovie items', function (): void {
    $this->getJson('/api/v1/cinema-movie', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/vnd.api+json');
});

it('forbids authenticated users without the CinemaMovie view permission', function (): void {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/v1/cinema-movie', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertForbidden();
});

it('returns an allowlisted JSON:API collection for CinemaMovie items', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new CinemaMovieExtension(),
        'cinema-movie.items.view',
    ));

    $this->getJson('/api/v1/cinema-movie', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('jsonapi.version', '1.0');
});
