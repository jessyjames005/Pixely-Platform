<?php

declare(strict_types=1);

use App\Core\Extensions\Discovery\ExtensionManifestReader;

it('reads an extension manifest', function () {
    $reader = new ExtensionManifestReader();

    $manifest = $reader->read(
        dirname(__DIR__, 4) . '/Fixtures/Extensions/Gallery/extension.json'
    );

    expect($manifest)->toBeArray();
    expect($manifest['name'])->toBe('gallery');
    expect($manifest['version'])->toBe('1.0.0');
    expect($manifest['class'])->toBe(
        'App\\Extensions\\Gallery\\GalleryExtension'
    );
});
