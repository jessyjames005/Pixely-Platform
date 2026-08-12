<?php

declare(strict_types=1);

use App\Core\Extensions\Configuration\ExtensionConfiguration;
use Tests\Fakes\Extensions\GalleryExtension;
use App\Core\Extensions\Configuration\InMemoryExtensionConfigurationRepository;

it('persists an extension configuration override', function () {
    $repository = new InMemoryExtensionConfigurationRepository();

    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
        $repository,
    );

    $configuration->set(
        'gallery.per_page',
        50,
    );

    $configuration->save();

    expect($repository->load('gallery'))
        ->toMatchArray([
            'gallery' => [
                'per_page' => 50,
            ],
        ]);
});

it('can save and load extension configuration', function () {
    $repository = new InMemoryExtensionConfigurationRepository();

    $repository->save('gallery', [
        'gallery' => [
            'per_page' => 50,
        ],
    ]);

    expect(
        $repository->load('gallery'),
    )->toBe([
        'gallery' => [
            'per_page' => 50,
        ],
    ]);
});

it('loads persisted configuration overrides', function () {
    $repository = new InMemoryExtensionConfigurationRepository();

    $repository->save('gallery', [
        'gallery' => [
            'per_page' => 50,
        ],
    ]);

    $configuration = new ExtensionConfiguration(
        new GalleryExtension(),
        $repository,
    );

    expect(
        $configuration->get('gallery.per_page'),
    )->toBe(50);

    expect(
        $configuration->all(),
    )->toMatchArray([
        'enabled' => true,
        'gallery' => [
            'per_page' => 50,
        ],
    ]);
});
