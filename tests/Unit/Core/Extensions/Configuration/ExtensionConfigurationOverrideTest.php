<?php

declare(strict_types=1);

use App\Core\Extensions\Configuration\ExtensionConfiguration;
use Tests\Fakes\Extensions\GalleryExtension;

it('overrides an extension default configuration value', function () {
    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
    );

    $configuration->set(
        'gallery.per_page',
        50,
    );

    expect($configuration->get('gallery.per_page'))
        ->toBe(50);
});

it('includes nested overrides in the effective configuration', function () {
    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
    );

    $configuration->set(
        'gallery.per_page',
        50,
    );

    expect($configuration->all())
        ->toMatchArray([
            'enabled' => true,
            'gallery' => [
                'per_page' => 50,
            ],
        ]);
});

it('returns the provided default for an unknown configuration value', function () {
    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
    );

    expect(
        $configuration->get('gallery.unknown', 'fallback'),
    )->toBe('fallback');
});

it('returns null for an unknown configuration value without a default', function () {
    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
    );

    expect(
        $configuration->get('gallery.unknown'),
    )->toBeNull();
});

it('can forget a configuration override', function () {
    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
    );

    $configuration->set(
        'gallery.per_page',
        50,
    );

    $configuration->forget(
        'gallery.per_page',
    );

    expect(
        $configuration->get('gallery.per_page'),
    )->toBe(20);
});

it('can determine whether a configuration override exists', function () {
    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
    );

    expect(
        $configuration->has('gallery.per_page'),
    )->toBeFalse();

    $configuration->set(
        'gallery.per_page',
        50,
    );

    expect(
        $configuration->has('gallery.per_page'),
    )->toBeTrue();

    $configuration->forget(
        'gallery.per_page',
    );

    expect(
        $configuration->has('gallery.per_page'),
    )->toBeFalse();
});

it('restores the default configuration after forgetting an override', function () {
    $extension = new GalleryExtension();

    $configuration = new ExtensionConfiguration(
        $extension,
    );

    $configuration->set(
        'gallery.per_page',
        50,
    );

    $configuration->forget(
        'gallery.per_page',
    );

    expect($configuration->all())
        ->toMatchArray([
            'enabled' => true,
            'gallery' => [
                'per_page' => 20,
            ],
        ]);
});
