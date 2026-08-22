<?php

declare(strict_types=1);

use App\Core\Extensions\Dependency\ExtensionDependencyResolver;
use App\Core\Extensions\Exceptions\ExtensionDependencyException;
use App\Core\Extensions\Exceptions\ExtensionDependencyCycleException;
use App\Core\Extensions\Manifest\ExtensionManifest;

function createManifest(
    string $id,
    array $dependencies = [],
): ExtensionManifest {
    return new ExtensionManifest(
        id: $id,
        name: ucfirst($id),
        version: '1.0.0',
        class: "Tests\\Fakes\\Extensions\\" . ucfirst($id) . 'Extension',
        path: 'app/Extensions/Gallery',
        dependencies: $dependencies,
    );
}

it('returns extensions without dependencies', function () {
    $resolver = new ExtensionDependencyResolver();

    $extensions = [
        createManifest('gallery'),
    ];

    $resolved = $resolver->resolve($extensions);

    expect($resolved)->toHaveCount(1);

    expect($resolved[0]->id)->toBe('gallery');
});

it('loads dependencies before dependent extensions', function () {
    $resolver = new ExtensionDependencyResolver();

    $extensions = [
        createManifest(
            'gallery',
            ['media'],
        ),
        createManifest('media'),
    ];

    $resolved = $resolver->resolve($extensions);

    $ids = array_map(
        static fn (ExtensionManifest $manifest): string => $manifest->id,
        $resolved,
    );

    expect($ids)->toBe([
        'media',
        'gallery',
    ]);
});

it('resolves multiple dependencies', function () {
    $resolver = new ExtensionDependencyResolver();

    $extensions = [
        createManifest(
            'gallery',
            ['media', 'users'],
        ),
        createManifest('users'),
        createManifest('media'),
    ];

    $resolved = $resolver->resolve($extensions);

    $ids = array_map(
        static fn (ExtensionManifest $manifest): string => $manifest->id,
        $resolved,
    );

    expect($ids)->toHaveCount(3);

    expect($ids[2])->toBe('gallery');

    expect(array_slice($ids, 0, 2))
        ->toContain('media')
        ->toContain('users');
});

it('rejects a missing dependency', function () {
    $resolver = new ExtensionDependencyResolver();

    $extensions = [
        createManifest(
            'gallery',
            ['media'],
        ),
    ];

    expect(
        fn () => $resolver->resolve($extensions),
    )->toThrow(
        ExtensionDependencyException::class,
        'Extension [gallery] requires missing extension [media].',
    );
});

it('rejects circular dependencies', function () {
    $resolver = new ExtensionDependencyResolver();

    $extensions = [
        createManifest(
            'gallery',
            ['media'],
        ),
        createManifest(
            'media',
            ['gallery'],
        ),
    ];

    expect(
        fn () => $resolver->resolve($extensions),
    )->toThrow(
        ExtensionDependencyCycleException::class,
        'Circular extension dependency detected: [gallery].',
    );
});
