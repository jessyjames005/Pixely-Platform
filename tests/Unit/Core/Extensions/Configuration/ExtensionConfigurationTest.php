<?php

declare(strict_types=1);

use App\Core\Extensions\Configuration\ExtensionConfiguration;
use Tests\Fakes\Extensions\GalleryExtension;

it('returns the default extension configuration', function () {
    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
    );

    expect($configuration->all())->toBeArray();
});

it('returns the extension default configuration', function () {
    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
    );

    expect($configuration->all())->toMatchArray([
        'enabled' => true,
    ]);
});

it('returns configuration declared by the extension', function () {
    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
    );

    expect($configuration->all())->toMatchArray([
        'enabled' => true,
        'gallery' => [
            'per_page' => 20,
        ],
    ]);
});
