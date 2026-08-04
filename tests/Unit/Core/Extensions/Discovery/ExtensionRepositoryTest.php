<?php

declare(strict_types=1);

use App\Core\Extensions\Discovery\{
    ExtensionRepository,
    ExtensionDiscoverer,
    ExtensionManifestReader
};

it('returns discovered extensions with manifests', function () {
    $repository = new ExtensionRepository(
        new ExtensionDiscoverer(),
        new ExtensionManifestReader()
    );

    $extensions = $repository->all(
        dirname(__DIR__, 5) . '/tests/Fixtures/Extensions'
    );

    expect($extensions)->toBeArray();

    expect($extensions)->not->toBeEmpty();

    expect($extensions[0])->toBeInstanceOf(
        \App\Core\Extensions\Contracts\ExtensionInterface::class
    );

    expect($extensions[0]->manifest()->name)->toBe('Gallery');
});
