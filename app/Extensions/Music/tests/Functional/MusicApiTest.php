<?php

declare(strict_types=1);

use App\Core\Extensions\Database\ExtensionMigrationRunner;
use App\Extensions\Music\MusicExtension;
use App\Extensions\Music\Providers\MusicServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Extensions\ExtensionApiTestHelper;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->register(MusicServiceProvider::class);
    $extension = new MusicExtension();
    $synchronizer = app(\App\Core\Extensions\Permissions\ExtensionPermissionSynchronizer::class);
    $synchronizer->sync($extension);
});

it('requires authentication to list Music items', function (): void {
    $this->getJson('/api/v1/music', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertUnauthorized();
});

it('allows users with the music view permission to list items', function (): void {
    /** @var TestCase $testCase */
    $testCase = $this;
    $testCase->actingAs(ExtensionApiTestHelper::createUserWithPermission(
        new MusicExtension(),
        'music.items.view',
    ));

    $this->getJson('/api/v1/music', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertOk();
});
