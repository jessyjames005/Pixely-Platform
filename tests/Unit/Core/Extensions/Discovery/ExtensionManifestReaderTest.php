<?php

declare(strict_types=1);

use App\Core\Extensions\Discovery\ExtensionManifestReader;

it('reads an extension manifest', function () {
    $reader = new ExtensionManifestReader();

    $manifest = $reader->read(
        dirname(__DIR__, 4) . '/Fixtures/Extensions/Gallery/extension.php'
    );

    expect($manifest)->toBeArray();

    expect($manifest)->toMatchArray([
        'name' => 'gallery',
        'version' => '1.0.0',
        'class' => 'App\\Extensions\\Gallery\\GalleryExtension',
    ]);
});

it('creates an extension manifest with dependencies', function () {
    $reader = new ExtensionManifestReader();

    $manifest = $reader->createManifest([
        'id' => 'gallery',
        'name' => 'Gallery',
        'version' => '1.0.0',
        'class' => 'App\\Extensions\\Gallery\\GalleryExtension',
        'requires' => [
            'media',
            'users',
        ],
    ]);

    expect($manifest)->not->toBeNull();

    expect($manifest->id)->toBe('gallery');

    expect($manifest->dependencies)->toBe([
        'media',
        'users',
    ]);
});

it('creates an extension manifest without dependencies', function () {
    $reader = new ExtensionManifestReader();

    $manifest = $reader->createManifest([
        'id' => 'gallery',
        'name' => 'Gallery',
        'version' => '1.0.0',
        'class' => 'App\\Extensions\\Gallery\\GalleryExtension',
    ]);

    expect($manifest)->not->toBeNull();

    expect($manifest->dependencies)->toBe([]);
});

it('ignores invalid dependencies', function () {
    $reader = new ExtensionManifestReader();

    $manifest = $reader->createManifest([
        'id' => 'gallery',
        'name' => 'Gallery',
        'version' => '1.0.0',
        'class' => 'App\\Extensions\\Gallery\\GalleryExtension',
        'requires' => [
            'media',
            123,
            null,
            'users',
        ],
    ]);

    expect($manifest)->not->toBeNull();

    expect($manifest->dependencies)->toBe([
        'media',
        'users',
    ]);
});
