<?php

declare(strict_types=1);

use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Update\ExtensionUpdatePlanner;

/**
 * Create a candidate extension manifest for update planner tests.
 *
 * @param string[] $dependencies Required extension identifiers.
 */
function updateCandidate(array $dependencies = []): ExtensionManifest
{
    return new ExtensionManifest(
        id: 'player',
        name: 'Player',
        version: '2.0.0',
        minimum_kernel_version: '1.0.0',
        class: 'App\\Extensions\\Player\\PlayerExtension',
        path: '/tmp/player',
        dependencies: $dependencies,
    );
}

it('creates a dependency-aware update plan without modifying installed versions', function () {
    $versions = ['player' => '1.0.0', 'media-core' => '2.1.0'];
    $plan = (new ExtensionUpdatePlanner())->plan(updateCandidate(['media-core']), $versions, ['media-core' => '2.0.0']);

    expect($plan['from'])->toBe('1.0.0')
        ->and($plan['to'])->toBe('2.0.0')
        ->and($plan['dependencies'])->toBe(['media-core' => '2.1.0'])
        ->and($versions['player'])->toBe('1.0.0');
});

it('rejects missing dependencies', function () {
    (new ExtensionUpdatePlanner())->plan(updateCandidate(['media-core']), ['player' => '1.0.0']);
})->throws(RuntimeException::class, 'requires missing extension');

it('rejects dependencies below their minimum version', function () {
    (new ExtensionUpdatePlanner())->plan(updateCandidate(['media-core']), ['player' => '1.0.0', 'media-core' => '1.0.0'], ['media-core' => '2.0.0']);
})->throws(RuntimeException::class, 'or newer');

it('rejects declared conflicts', function () {
    (new ExtensionUpdatePlanner())->plan(updateCandidate(), ['player' => '1.0.0', 'legacy-player' => '1.0.0'], [], ['legacy-player']);
})->throws(RuntimeException::class, 'conflicts with installed extension');

it('rejects downgrade and same-version packages', function () {
    (new ExtensionUpdatePlanner())->plan(updateCandidate(), ['player' => '2.0.0']);
})->throws(RuntimeException::class, 'must be newer');


it('rejects a dependency cycle introduced by the candidate', function () {
    (new ExtensionUpdatePlanner())->plan(
        updateCandidate(['media-core']),
        ['player' => '1.0.0', 'media-core' => '2.1.0'],
        [],
        [],
        ['media-core' => ['player']],
    );
})->throws(RuntimeException::class, 'Circular extension dependency');
