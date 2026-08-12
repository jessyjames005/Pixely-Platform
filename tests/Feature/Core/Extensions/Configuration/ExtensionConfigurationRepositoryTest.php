<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use App\Core\Extensions\Configuration\DatabaseExtensionConfigurationRepository;

uses(
    TestCase::class,
    RefreshDatabase::class,
);

it('has an extension configurations table', function () {
    expect(
        Schema::hasTable('extension_configurations'),
    )->toBeTrue();
});

it('has the expected extension configuration columns', function () {
    expect(Schema::hasColumn(
        'extension_configurations',
        'extension_id',
    ))->toBeTrue();

    expect(Schema::hasColumn(
        'extension_configurations',
        'configuration',
    ))->toBeTrue();
});

it('persists extension configuration in the database', function () {
    $repository = new DatabaseExtensionConfigurationRepository();

    $repository->save('gallery', [
        'gallery' => [
            'per_page' => 50,
        ],
    ]);

    expect($repository->load('gallery'))
        ->toBe([
            'gallery' => [
                'per_page' => 50,
            ],
        ]);
});

it('resolves the database configuration repository from the container', function () {
    $repository = app(
        \App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface::class,
    );

    expect($repository)
        ->toBeInstanceOf(
            \App\Core\Extensions\Configuration\DatabaseExtensionConfigurationRepository::class,
        );
});

it('persists configuration through the extension configuration service', function () {
    $repository = app(
        \App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface::class,
    );

    $repository->save('gallery', [
        'gallery' => [
            'per_page' => 50,
        ],
    ]);

    $configuration = new \App\Core\Extensions\Configuration\ExtensionConfiguration(
        new \Tests\Fakes\Extensions\GalleryExtension(),
        $repository,
    );

    expect(
        $configuration->get('gallery.per_page'),
    )->toBe(50);
});

it('resolves extension configuration with the database repository', function () {
    $configuration = app()->make(
        \App\Core\Extensions\Configuration\ExtensionConfiguration::class,
        [
            'extension' => new \Tests\Fakes\Extensions\GalleryExtension(),
        ],
    );

    $configuration->set(
        'gallery.per_page',
        50,
    );

    $configuration->save();

    expect($configuration->get('gallery.per_page'))
        ->toBe(50);

    expect(
        \DB::table('extension_configurations')
            ->where('extension_id', 'gallery')
            ->exists(),
    )->toBeTrue();
});
