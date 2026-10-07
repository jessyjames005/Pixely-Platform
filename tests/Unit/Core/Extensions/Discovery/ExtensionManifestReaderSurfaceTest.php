<?php

declare(strict_types=1);

use App\Core\Extensions\Discovery\ExtensionManifestReader;

it('preserves declared extension surfaces', function () {
    $manifest = (new ExtensionManifestReader())->createManifest([
        'id' => 'gallery',
        'name' => 'Gallery',
        'version' => '1.0.0',
        'class' => 'App\\Extensions\\Gallery\\GalleryExtension',
        'surfaces' => ['public', 'user', 'admin', 'api'],
    ], 'app/Extensions/Gallery');

    expect($manifest)->not->toBeNull()
        ->and($manifest->surfaces)->toBe(['public', 'user', 'admin', 'api']);
});

it('rejects unknown extension surfaces', function () {
    $manifest = (new ExtensionManifestReader())->createManifest([
        'id' => 'gallery',
        'name' => 'Gallery',
        'version' => '1.0.0',
        'class' => 'App\\Extensions\\Gallery\\GalleryExtension',
        'surfaces' => ['public', 'unknown'],
    ], 'app/Extensions/Gallery');

    expect($manifest)->toBeNull();
});
