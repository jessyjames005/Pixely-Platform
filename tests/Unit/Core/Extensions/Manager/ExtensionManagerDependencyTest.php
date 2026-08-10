<?php

declare(strict_types=1);

use App\Core\Extensions\Dependency\ExtensionDependencyResolver;
use App\Core\Extensions\Exceptions\ExtensionDependencyException;
use App\Core\Extensions\Discovery\ExtensionDiscoverer;
use App\Core\Extensions\Discovery\ExtensionManifestReader;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Registry\ExtensionRegistry;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use Tests\Fakes\Extensions\MediaExtension;
use Tests\Fakes\Extensions\GalleryExtension;
use Tests\Fakes\Extensions\MediaWithStorageExtension;
use Tests\Fakes\Extensions\StorageExtension;
use App\Core\Extensions\Exceptions\ExtensionDependencyCycleException;
use Tests\Fakes\Extensions\CyclicGalleryExtension;
use Tests\Fakes\Extensions\CyclicMediaExtension;

function createDependencyManager(): ExtensionManager
{
    return new ExtensionManager(
        new ExtensionRegistry(),
        new ExtensionRepository(
            new ExtensionDiscoverer(),
            new ExtensionManifestReader(),
            new ExtensionDependencyResolver(),
        ),
        new InMemoryExtensionStateRepository(),
    );
}

it('rejects enabling an extension when a dependency is disabled', function () {
    $manager = createDependencyManager();

    $media = new MediaExtension();
    $gallery = new GalleryExtension();

    $manager->register($media);
    $manager->register($gallery);

    $manager->disable('media');

    expect(
        fn() => $manager->enable('gallery'),
    )->toThrow(
        ExtensionDependencyException::class,
    );
});

it('rejects enabling an extension when a dependency is missing', function () {
    $manager = createDependencyManager();

    $gallery = new GalleryExtension();

    $manager->register($gallery);

    expect(
        fn() => $manager->enable('gallery'),
    )->toThrow(
        ExtensionDependencyException::class,
    );
});

it('rejects enabling an extension when a transitive dependency is disabled', function () {
    $manager = createDependencyManager();

    $storage = new StorageExtension();
    $media = new MediaWithStorageExtension();
    $gallery = new GalleryExtension();

    $manager->register($storage);
    $manager->register($media);
    $manager->register($gallery);

    $manager->disable('storage');

    expect(
        fn() => $manager->enable('gallery'),
    )->toThrow(
        ExtensionDependencyException::class,
    );
});

it('rejects enabling an extension with a circular dependency', function () {
    $manager = createDependencyManager();

    $gallery = new CyclicGalleryExtension();
    $media = new CyclicMediaExtension();

    $manager->register($gallery);
    $manager->register($media);

    expect(
        fn() => $manager->enable('gallery'),
    )->toThrow(
        ExtensionDependencyCycleException::class,
    );
});
