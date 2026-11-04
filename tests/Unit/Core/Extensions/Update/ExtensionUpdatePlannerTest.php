<?php

declare(strict_types=1);

use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Core\Extensions\Update\ExtensionUpdatePlanner;

/**
 * Create a candidate extension manifest for update planner tests.
 *
 * @param string[] $dependencies Required extension identifiers.
 */
function updateCandidate(
    array $dependencies = [],
    string $id = 'player',
    string $version = '2.0.0',
): ExtensionManifest {
    return new ExtensionManifest(
        id: $id,
        name: ucfirst($id),
        version: $version,
        minimum_kernel_version: '1.0.0',
        class: 'App\\Extensions\\' . ucfirst($id) . '\\' . ucfirst($id) . 'Extension',
        path: '/' . $id,
        dependencies: $dependencies,
    );
}

/**
 * @param array<string, string> $requiredVersions
 * @param string[] $conflicts
 * @return array{manifest: ExtensionManifest, required_versions: array<string, string>, conflicts: string[]}
 */
function updateBatchCandidate(
    ExtensionManifest $manifest,
    array $requiredVersions = [],
    array $conflicts = [],
): array {
    return [
        'manifest' => $manifest,
        'required_versions' => $requiredVersions,
        'conflicts' => $conflicts,
    ];
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

it('plans all update candidates in dependency-first order', function () {
    $plans = (new ExtensionUpdatePlanner())->planBatch(
        [
            updateBatchCandidate(updateCandidate(['media-core'], 'player')),
            updateBatchCandidate(updateCandidate([], 'media-core')),
        ],
        ['player' => '1.0.0', 'media-core' => '1.0.0'],
    );

    expect(array_column($plans, 'extension'))->toBe(['media-core', 'player']);
});

it('accepts a required minimum version supplied by an included candidate', function () {
    $plans = (new ExtensionUpdatePlanner())->planBatch(
        [
            updateBatchCandidate(
                updateCandidate(['media-core'], 'player'),
                ['media-core' => '2.0.0'],
            ),
            updateBatchCandidate(updateCandidate([], 'media-core', '2.0.0')),
        ],
        ['player' => '1.0.0', 'media-core' => '1.0.0'],
    );

    expect($plans[1]['extension'])->toBe('player')
        ->and($plans[1]['dependencies'])->toBe(['media-core' => '2.0.0']);
});

it('rejects a missing dependency in the complete candidate set', function () {
    (new ExtensionUpdatePlanner())->planBatch(
        [updateBatchCandidate(updateCandidate(['media-core']))],
        ['player' => '1.0.0'],
    );
})->throws(RuntimeException::class, 'requires missing extension');

it('rejects a dependency whose installed and candidate versions are below the minimum', function () {
    (new ExtensionUpdatePlanner())->planBatch(
        [
            updateBatchCandidate(
                updateCandidate(['media-core'], 'player'),
                ['media-core' => '2.0.0'],
            ),
            updateBatchCandidate(updateCandidate([], 'media-core', '1.5.0')),
        ],
        ['player' => '1.0.0', 'media-core' => '1.0.0'],
    );
})->throws(RuntimeException::class, 'version [2.0.0] or newer');

it('rejects a conflict with an installed extension', function () {
    (new ExtensionUpdatePlanner())->planBatch(
        [updateBatchCandidate(updateCandidate(), [], ['legacy-player'])],
        ['player' => '1.0.0', 'legacy-player' => '1.0.0'],
    );
})->throws(RuntimeException::class, 'conflicts with installed extension');

it('rejects cycles among update candidates', function () {
    (new ExtensionUpdatePlanner())->planBatch(
        [
            updateBatchCandidate(updateCandidate(['media-core'], 'player')),
            updateBatchCandidate(updateCandidate(['player'], 'media-core')),
        ],
        ['player' => '1.0.0', 'media-core' => '1.0.0'],
    );
})->throws(RuntimeException::class, 'Circular extension dependency');

it('orders otherwise independent candidates deterministically', function () {
    $plans = (new ExtensionUpdatePlanner())->planBatch(
        [
            updateBatchCandidate(updateCandidate([], 'zeta')),
            updateBatchCandidate(updateCandidate([], 'beta')),
            updateBatchCandidate(updateCandidate([], 'alpha')),
        ],
        ['zeta' => '1.0.0', 'beta' => '1.0.0', 'alpha' => '1.0.0'],
    );

    expect(array_column($plans, 'extension'))->toBe(['alpha', 'beta', 'zeta']);
});

it('rejects candidates that are not installed before resolving the batch', function () {
    (new ExtensionUpdatePlanner())->planBatch(
        [updateBatchCandidate(updateCandidate([], 'new-extension'))],
        [],
    );
})->throws(RuntimeException::class, 'is not installed');

it('rejects duplicate update candidates for one extension', function () {
    (new ExtensionUpdatePlanner())->planBatch(
        [
            updateBatchCandidate(updateCandidate()),
            updateBatchCandidate(updateCandidate()),
        ],
        ['player' => '1.0.0'],
    );
})->throws(RuntimeException::class, 'More than one update candidate');

it('rejects an update candidate without a valid manifest', function () {
    (new ExtensionUpdatePlanner())->planBatch(
        [['manifest' => 'invalid']],
        [],
    );
})->throws(InvalidArgumentException::class, 'must include an extension manifest');

it('rejects invalid required version metadata', function () {
    (new ExtensionUpdatePlanner())->planBatch(
        [[
            'manifest' => updateCandidate(),
            'required_versions' => ['media-core' => false],
        ]],
        ['player' => '1.0.0'],
    );
})->throws(InvalidArgumentException::class, 'metadata for extension [player] is invalid');

it('rejects invalid conflict metadata', function () {
    (new ExtensionUpdatePlanner())->planBatch(
        [[
            'manifest' => updateCandidate(),
            'conflicts' => [42],
        ]],
        ['player' => '1.0.0'],
    );
})->throws(InvalidArgumentException::class, 'metadata for extension [player] is invalid');
