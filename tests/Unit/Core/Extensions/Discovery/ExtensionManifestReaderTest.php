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
