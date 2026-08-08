<?php

declare(strict_types=1);

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Dependency\ExtensionDependencyResolver;
use App\Core\Extensions\Discovery\ExtensionDiscoverer;
use App\Core\Extensions\Discovery\ExtensionManifestReader;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Manifest\ExtensionManifest;

it('returns discovered extensions with manifests', function () {
    $repository = new ExtensionRepository(
        new ExtensionDiscoverer(),
        new ExtensionManifestReader(),
        new ExtensionDependencyResolver(),
    );

    $extensions = $repository->all(
        dirname(__DIR__, 5) . '/tests/Fixtures/Extensions',
    );

    expect($extensions)->toBeArray();

    expect($extensions)->not->toBeEmpty();

    expect($extensions[0])->toBeInstanceOf(
        ExtensionInterface::class,
    );
});

it('returns discovered extension manifests in dependency order', function () {
    $repository = new ExtensionRepository(
        new ExtensionDiscoverer(),
        new ExtensionManifestReader(),
        new ExtensionDependencyResolver(),
    );

    $manifests = $repository->manifests(
        dirname(__DIR__, 5) . '/tests/Fixtures/Extensions',
    );

    expect($manifests)->toHaveCount(2);

    expect($manifests)->each->toBeInstanceOf(
        ExtensionManifest::class,
    );

    $ids = array_map(
        static fn (ExtensionManifest $manifest): string => $manifest->id,
        $manifests,
    );

    expect($ids)->toBe([
        'media',
        'gallery',
    ]);
});

it('returns discovered extension manifests with their dependencies', function () {
    $repository = new ExtensionRepository(
        new ExtensionDiscoverer(),
        new ExtensionManifestReader(),
        new ExtensionDependencyResolver(),
    );

    $manifests = $repository->manifests(
        dirname(__DIR__, 5) . '/tests/Fixtures/Extensions',
    );

    $manifestsById = [];

    foreach ($manifests as $manifest) {
        $manifestsById[$manifest->id] = $manifest;
    }

    expect($manifestsById)->toHaveKeys([
        'gallery',
        'media',
    ]);

    expect($manifestsById['gallery']->dependencies)->toBe([
        'media',
    ]);

    expect($manifestsById['media']->dependencies)->toBe([]);
});
