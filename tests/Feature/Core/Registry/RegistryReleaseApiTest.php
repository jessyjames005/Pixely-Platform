<?php

declare(strict_types=1);

use App\Core\Registry\Models\RegistryPackage;
use App\Core\Registry\Models\RegistryRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('serves a public release list with the exact envelope and complete raw manifest', function (): void {
    $manifest = registryApiManifest('extension', 'vendor/gallery', '1.2.0', 'stable');
    storeRegistryApiRelease('extension', 'vendor/gallery', '1.2.0', 'stable', $manifest);

    $response = $this->get('/api/v1/releases?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'vendor/gallery',
    ]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/json; charset=utf-8');

    expect(array_keys($response->json()))->toEqualCanonicalizing(['data', 'meta'])
        ->and(array_keys($response->json('data.0')))->toEqual(['manifest'])
        ->and($response->json('data.0.manifest'))->toBe(json_decode($manifest, true, 512, JSON_THROW_ON_ERROR))
        ->and($response->json('meta'))->toMatchArray([
            'per_page' => 20,
            'has_more' => false,
            'next_cursor' => null,
        ])
        ->and($response->getContent())->toContain($manifest);
});

it('orders releases by SemVer and returns only versions strictly newer than after_version', function (): void {
    foreach (['1.9.9', '1.10.0', '2.0.0-beta.1', '2.0.0', '2.0.1+build.1', '2.0.1+build.2'] as $version) {
        storeRegistryApiRelease('extension', 'vendor/versions', $version, 'stable');
    }

    $response = $this->getJson('/api/v1/releases?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'vendor/versions',
        'after_version' => '1.10.0',
    ]));

    $response->assertOk();
    expect(array_map(
        static fn (array $item): string => $item['manifest']['release']['version'],
        $response->json('data'),
    ))
        ->toBe(['2.0.1+build.2', '2.0.1+build.1', '2.0.0', '2.0.0-beta.1']);
});

it('defaults to stable and filters an explicitly requested release channel', function (): void {
    storeRegistryApiRelease('extension', 'vendor/channels', '3.0.0', 'stable');
    storeRegistryApiRelease('extension', 'vendor/channels', '4.0.0-beta.1', 'beta');
    storeRegistryApiRelease('extension', 'vendor/channels', '5.0.0-dev.1', 'dev');

    $defaultResponse = $this->getJson('/api/v1/releases?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'vendor/channels',
    ]));
    $betaResponse = $this->getJson('/api/v1/releases?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'vendor/channels',
        'channel' => 'beta',
    ]));

    expect(array_map(
        static fn (array $item): string => $item['manifest']['release']['channel'],
        $defaultResponse->json('data'),
    ))->toBe(['stable'])
        ->and(array_map(
            static fn (array $item): string => $item['manifest']['release']['channel'],
            $betaResponse->json('data'),
        ))->toBe(['beta']);
});

it('returns empty metadata for a package with no matching releases', function (): void {
    RegistryPackage::query()->create([
        'type' => 'extension',
        'package_id' => 'vendor/empty',
        'name' => 'Empty package',
    ]);

    $response = $this->getJson('/api/v1/releases?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'vendor/empty',
    ]));

    $response->assertOk();
    expect($response->json())->toMatchArray([
        'data' => [],
        'meta' => [
            'per_page' => 20,
            'next_cursor' => null,
            'has_more' => false,
        ],
    ]);
});

it('continues a stable cursor snapshot and rejects tampered or differently bound cursors', function (): void {
    foreach (['1.0.0', '2.0.0', '3.0.0', '4.0.0', '5.0.0'] as $version) {
        storeRegistryApiRelease('extension', 'vendor/paged', $version, 'stable');
    }

    $filters = [
        'package_type' => 'extension',
        'package_id' => 'vendor/paged',
        'per_page' => 2,
    ];
    $firstPage = $this->getJson('/api/v1/releases?' . http_build_query($filters));
    $cursor = $firstPage->json('meta.next_cursor');

    expect($cursor)->toBeString()->not->toBe('');
    $firstPage->assertOk();
    expect(array_map(
        static fn (array $item): string => $item['manifest']['release']['version'],
        $firstPage->json('data'),
    ))->toBe(['5.0.0', '4.0.0'])
        ->and($firstPage->json('meta.has_more'))->toBeTrue();

    storeRegistryApiRelease('extension', 'vendor/paged', '6.0.0', 'stable');

    $nextPage = $this->getJson('/api/v1/releases?' . http_build_query($filters + ['cursor' => $cursor]));
    $nextPage->assertOk();
    expect(array_map(
        static fn (array $item): string => $item['manifest']['release']['version'],
        $nextPage->json('data'),
    ))->toBe(['3.0.0', '2.0.0']);

    $finalPage = $this->getJson('/api/v1/releases?' . http_build_query([
        ...$filters,
        'cursor' => $nextPage->json('meta.next_cursor'),
    ]));
    $finalPage->assertOk();
    expect(array_map(
        static fn (array $item): string => $item['manifest']['release']['version'],
        $finalPage->json('data'),
    ))->toBe(['1.0.0'])
        ->and($finalPage->json('meta.has_more'))->toBeFalse()
        ->and($finalPage->json('meta.next_cursor'))->toBeNull();

    $lastCharacter = substr($cursor, -1);
    $tamperedCursor = substr($cursor, 0, -1) . ($lastCharacter === 'A' ? 'B' : 'A');
    $tamperedResponse = $this->getJson(
        '/api/v1/releases?' . http_build_query($filters + ['cursor' => $tamperedCursor]),
    );

    expectRegistryProblem($tamperedResponse, 400, 'invalid_cursor');

    $oversizedCursor = $this->getJson(
        '/api/v1/releases?' . http_build_query($filters + ['cursor' => str_repeat('x', 2049)]),
    );
    expectRegistryProblem($oversizedCursor, 400, 'invalid_cursor');

    $reusedResponse = $this->getJson('/api/v1/releases?' . http_build_query([
        ...$filters,
        'channel' => 'beta',
        'cursor' => $cursor,
    ]));

    expectRegistryProblem($reusedResponse, 400, 'invalid_cursor');
});

it('binds cursor continuation to normalized runtime filters', function (): void {
    foreach (['1.0.0', '2.0.0', '3.0.0'] as $version) {
        storeRegistryApiRelease('extension', 'vendor/runtime-cursor', $version, 'stable');
    }

    $filters = [
        'package_type' => 'extension',
        'package_id' => 'vendor/runtime-cursor',
        'per_page' => 1,
        'runtime' => ['php' => '8.3.1'],
    ];
    $firstPage = $this->getJson('/api/v1/releases?' . http_build_query($filters));
    $firstPage->assertOk();
    $cursor = $firstPage->json('meta.next_cursor');

    expect($cursor)->toBeString()->not->toBe('');

    $changedRuntime = $this->getJson('/api/v1/releases?' . http_build_query([
        ...$filters,
        'runtime' => ['php' => '8.4.0'],
        'cursor' => $cursor,
    ]));

    expectRegistryProblem($changedRuntime, 400, 'invalid_cursor');
});

it('validates required fields, supported values, SemVer, query length and page size boundaries', function (): void {
    $invalidQueries = [
        [],
        ['package_type' => 'unknown', 'package_id' => 'vendor/item'],
        ['package_type' => 'extension', 'package_id' => ''],
        ['package_type' => 'extension', 'package_id' => str_repeat('x', 256)],
        ['package_type' => 'extension', 'package_id' => 'vendor/item', 'channel' => 'preview'],
        ['package_type' => 'extension', 'package_id' => 'vendor/item', 'after_version' => '1.0'],
        ['package_type' => 'extension', 'package_id' => 'vendor/item', 'per_page' => '0'],
        ['package_type' => 'extension', 'package_id' => 'vendor/item', 'per_page' => '101'],
        ['package_type' => 'extension', 'package_id' => 'vendor/item', 'per_page' => '1.5'],
        ['package_type' => 'extension', 'package_id' => 'vendor/item', 'unused' => str_repeat('x', 4100)],
    ];

    foreach ($invalidQueries as $query) {
        $response = $this->getJson('/api/v1/releases?' . http_build_query($query));
        expectRegistryProblem($response, 400, 'invalid_query');
    }

    $packageId = 'vendor/boundaries';
    storeRegistryApiRelease('extension', $packageId, '1.0.0', 'stable');

    foreach ([1, 100] as $perPage) {
        $response = $this->getJson('/api/v1/releases?' . http_build_query([
            'package_type' => 'extension',
            'package_id' => $packageId,
            'per_page' => $perPage,
        ]));

        $response->assertOk();
        expect($response->json('meta.per_page'))->toBe($perPage);
    }
});

it('validates optional runtime filters while leaving them advisory', function (): void {
    RegistryPackage::query()->create([
        'type' => 'extension',
        'package_id' => 'vendor/runtime',
        'name' => 'Runtime package',
    ]);

    $valid = $this->getJson('/api/v1/releases?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'vendor/runtime',
        'runtime' => [
            'php' => '8.3.1',
            'pixely' => '1.2.0',
            'laravel' => '13.8.0',
        ],
    ]));
    $valid->assertOk()->assertJsonPath('data', []);

    $invalidRuntimes = [
        ['runtime' => '8.3.1'],
        ['runtime' => ['php' => '8.3']],
        ['runtime' => ['php' => ['major' => '8']]],
        ['runtime' => ['node' => '22.0.0']],
        ['runtime' => ['php' => '8.3.1', 'laravel' => '']],
        ['runtime' => ['php' => '8.3.1', 0 => '1.0.0']],
    ];

    foreach ($invalidRuntimes as $invalidRuntime) {
        $response = $this->getJson('/api/v1/releases?' . http_build_query([
            'package_type' => 'extension',
            'package_id' => 'vendor/runtime',
            ...$invalidRuntime,
        ]));

        expectRegistryProblem($response, 400, 'invalid_query');
    }
});

it('rate limits requests independently by IP and returns a Problem Details 429', function (): void {
    $ip = '198.51.100.71';
    $otherIp = '198.51.100.72';
    clearRegistryApiLimiter('ip-' . hash('sha256', $ip));
    clearRegistryApiLimiter('ip-' . hash('sha256', $otherIp));

    for ($attempt = 1; $attempt <= 60; $attempt++) {
        $client = 'registry-ip-test-' . $attempt;
        clearRegistryApiLimiter('client-' . hash('sha256', $client));
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders(['X-Pixely-Client' => $client])
            ->getJson('/api/v1/releases');
        $response->assertBadRequest();
    }

    clearRegistryApiLimiter('client-' . hash('sha256', 'registry-ip-test-over-limit'));
    $limited = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->withHeaders(['X-Pixely-Client' => 'registry-ip-test-over-limit'])
        ->getJson('/api/v1/releases');
    expectRegistryProblem($limited, 429, 'rate_limited');
    expect((int) $limited->headers->get('Retry-After'))->toBeGreaterThan(0);

    $otherIpResponse = $this->withServerVariables(['REMOTE_ADDR' => $otherIp])
        ->withHeaders(['X-Pixely-Client' => 'registry-ip-test-over-limit'])
        ->getJson('/api/v1/releases');
    $otherIpResponse->assertBadRequest();
});

it('rate limits client labels independently of IP and falls back safely for invalid labels', function (): void {
    $client = 'registry-client-test';
    $clientKey = 'client-' . hash('sha256', $client);
    clearRegistryApiLimiter($clientKey);
    for ($ipIndex = 0; $ipIndex < 3; $ipIndex++) {
        $ip = '198.51.100.' . (80 + $ipIndex);
        clearRegistryApiLimiter('ip-' . hash('sha256', $ip));
    }

    for ($attempt = 1; $attempt <= 120; $attempt++) {
        $ip = '198.51.100.' . (80 + (($attempt - 1) % 3));
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders(['X-Pixely-Client' => $client])
            ->getJson('/api/v1/releases');
        $response->assertBadRequest();
    }

    $limited = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.83'])
        ->withHeaders(['X-Pixely-Client' => $client])
        ->getJson('/api/v1/releases');
    expectRegistryProblem($limited, 429, 'rate_limited');
    expect((int) $limited->headers->get('Retry-After'))->toBeGreaterThan(0);

    clearRegistryApiLimiter('client-' . hash('sha256', 'registry-another-client'));
    $differentClient = $this->withHeaders(['X-Pixely-Client' => 'registry-another-client'])
        ->getJson('/api/v1/releases');
    $differentClient->assertBadRequest();

    $fallbackIp = '198.51.100.84';
    clearRegistryApiLimiter('ip-' . hash('sha256', $fallbackIp));
    clearRegistryApiLimiter('client-ip-' . hash('sha256', $fallbackIp));
    foreach (['bad label', str_repeat('x', 129), 'bad/value'] as $invalidClient) {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $fallbackIp])
            ->withHeaders(['X-Pixely-Client' => $invalidClient])
            ->getJson('/api/v1/releases');
        $response->assertBadRequest();
    }
});

it('resolves an exact release and reports package and release misses separately', function (): void {
    $manifest = registryApiManifest('extension', 'vendor/exact', '1.3.0', 'beta');
    storeRegistryApiRelease('extension', 'vendor/exact', '1.3.0', 'beta', $manifest);
    storeRegistryApiRelease('extension', 'vendor/exact', '1.3.0-rc.1', 'beta');

    $resolved = $this->get('/api/v1/releases/resolve?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'vendor/exact',
        'version' => '1.3.0',
    ]));
    $resolved->assertOk();
    expect($resolved->headers->get('Cache-Control'))->toContain('max-age=300');
    expect($resolved->json('data.manifest'))->toBe(json_decode($manifest, true, 512, JSON_THROW_ON_ERROR))
        ->and($resolved->getContent())->toContain($manifest);

    $missingPackage = $this->getJson('/api/v1/releases/resolve?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'vendor/missing',
        'version' => '1.3.0',
    ]));
    expectRegistryProblem($missingPackage, 404, 'package_not_found');

    $differentPackageCase = $this->getJson('/api/v1/releases/resolve?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'Vendor/exact',
        'version' => '1.3.0',
    ]));
    expectRegistryProblem($differentPackageCase, 404, 'package_not_found');

    $differentVersionCase = $this->getJson('/api/v1/releases/resolve?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'vendor/exact',
        'version' => '1.3.0-RC.1',
    ]));
    expectRegistryProblem($differentVersionCase, 404, 'release_not_found');

    $missingRelease = $this->getJson('/api/v1/releases/resolve?' . http_build_query([
        'package_type' => 'extension',
        'package_id' => 'vendor/exact',
        'version' => '1.4.0',
    ]));
    expectRegistryProblem($missingRelease, 404, 'release_not_found');
});

it('validates exact-release required fields and version boundaries', function (): void {
    $invalidQueries = [
        [],
        ['package_type' => 'extension', 'package_id' => 'vendor/item'],
        ['package_type' => 'extension', 'package_id' => 'vendor/item', 'version' => 'v1.0.0'],
    ];

    foreach ($invalidQueries as $query) {
        $response = $this->getJson('/api/v1/releases/resolve?' . http_build_query($query));
        expectRegistryProblem($response, 400, 'invalid_query');
    }
});

function expectRegistryProblem(\Illuminate\Testing\TestResponse $response, int $status, string $code): void
{
    $response->assertStatus($status)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);

    expect($response->json('code'))->toBe($code)
        ->and($response->json('status'))->toBe($status);
}

function clearRegistryApiLimiter(string $key): void
{
    RateLimiter::clear(md5('registry-public' . $key));
}

function storeRegistryApiRelease(
    string $packageType,
    string $packageId,
    string $version,
    string $channel,
    ?string $manifest = null,
): RegistryRelease {
    $package = RegistryPackage::query()->firstOrCreate(
        ['type' => $packageType, 'package_id' => $packageId],
        ['name' => 'Registry API test package'],
    );
    $manifest ??= registryApiManifest($packageType, $packageId, $version, $channel);

    return $package->releases()->create([
        'version' => $version,
        'channel' => $channel,
        'published_at' => '2026-01-01T00:00:00Z',
        'package_name' => 'Registry API test package',
        'manifest_json' => $manifest,
        'manifest_fingerprint' => hash('sha256', $manifest),
    ]);
}

function registryApiManifest(string $packageType, string $packageId, string $version, string $channel): string
{
    return json_encode([
        'schema' => 'pixely.release-manifest',
        'schema_version' => 1,
        'package' => [
            'type' => $packageType,
            'id' => $packageId,
            'name' => 'Registry API test package',
        ],
        'release' => [
            'version' => $version,
            'channel' => $channel,
            'published_at' => '2026-01-01T00:00:00Z',
            'release_notes' => null,
        ],
        'compatibility' => ['pixely' => '^1.0'],
        'dependencies' => ['requires' => [], 'conflicts' => []],
        'artifact' => [
            'provider' => 'pixely',
            'uri' => 'https://updates.pixely.example/test.zip',
            'filename' => 'test.zip',
            'size' => 1024,
            'sha256' => str_repeat('a', 64),
            'signature' => [
                'algorithm' => 'Ed25519',
                'key_id' => 'test-key',
                'value' => base64_encode(str_repeat('s', 64)),
            ],
        ],
        'signature' => [
            'algorithm' => 'Ed25519',
            'key_id' => 'test-key',
            'value' => base64_encode(str_repeat('m', 64)),
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
