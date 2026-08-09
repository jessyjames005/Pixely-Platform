<?php

declare(strict_types=1);

use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Registry\ExtensionRegistry;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Discovery\ExtensionDiscoverer;
use App\Core\Extensions\Discovery\ExtensionManifestReader;
use Tests\Fakes\Extensions\FakeExtension;

function createLifecycleManager(): ExtensionManager
{
    return new ExtensionManager(
        new ExtensionRegistry(),
        new ExtensionRepository(
            new ExtensionDiscoverer(),
            new ExtensionManifestReader(),
            new \App\Core\Extensions\Dependency\ExtensionDependencyResolver(),
        ),
        new InMemoryExtensionStateRepository(),
    );
}

it('stores an extension as registered', function () {
    $manager = createLifecycleManager();

    $extension = new FakeExtension();

    $manager->register($extension);

    $state = $manager->findState('gallery');

    expect($state)->not->toBeNull();
    expect($state->extension)->toBe($extension);
    expect($state->status)->toBe(ExtensionStatus::Enabled);
});

it('updates the extension state when booted', function () {
    $manager = createLifecycleManager();

    $extension = new FakeExtension();

    $manager->register($extension);
    $manager->boot();

    $state = $manager->findState('gallery');

    expect($state)->not->toBeNull();
    expect($state->status)->toBe(ExtensionStatus::Enabled);
});
