<?php

declare(strict_types=1);

use App\Core\Registry\Models\RegistryPackage;
use App\Core\Registry\Models\RegistryRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('lists packages publicly with their latest stable release summary', function (): void {
    $package = storeCatalogPackage('extension', 'vendor/gallery', 'Gallery Tools');
    storeCatalogRelease($package, '1.0.0', 'stable');
    storeCatalogRelease($package, '2.0.0-beta.1', 'beta');
    storeCatalogRelease($package, '1.5.0', 'stable');

    $response = $this->getJson('/api/v1/packages');

    $response->assertOk()
        ->assertJsonPath('data.0.type', 'extension')
        ->assertJsonPath('data.0.package_id', 'vendor/gallery')
        ->assertJsonPath('data.0.name', 'Gallery Tools')
        ->assertJsonPath('data.0.latest_release.version', '1.5.0')
        ->assertJsonPath('data.0.latest_release.channel', 'stable')
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.has_more', false)
        ->assertJsonPath('meta.next_cursor', null);
    expect($response->headers->get('Cache-Control'))->toContain('public')
        ->toContain('max-age=60');
});

it('searches names and package IDs case insensitively and filters package types', function (): void {
    $gallery = storeCatalogPackage('extension', 'vendor/gallery', 'Gallery Toolkit');
    storeCatalogRelease($gallery, '1.0.0', 'stable');
    $theme = storeCatalogPackage('theme', 'vendor/gallery-theme', 'Dark Theme');
    storeCatalogRelease($theme, '1.0.0', 'stable');
    $cinema = storeCatalogPackage('extension', 'vendor/cinema', 'Cinema Tools');
    storeCatalogRelease($cinema, '1.0.0', 'stable');

    $byName = $this->getJson('/api/v1/packages?' . http_build_query(['q' => 'GALLERY']));
    $byName->assertOk();
    expect(array_column($byName->json('data'), 'package_id'))->toBe([
        'vendor/gallery',
        'vendor/gallery-theme',
    ]);

    $literalWildcard = storeCatalogPackage('extension', 'vendor/100-percent', '100% Extension');
    storeCatalogRelease($literalWildcard, '1.0.0', 'stable');
    $byLiteralWildcard = $this->getJson('/api/v1/packages?' . http_build_query(['q' => '%']));
    $byLiteralWildcard->assertOk();
    expect(array_column($byLiteralWildcard->json('data'), 'package_id'))->toBe(['vendor/100-percent']);

    $byId = $this->getJson('/api/v1/packages?' . http_build_query([
        'q' => 'CINEMA',
        'package_type' => 'extension',
    ]));
    $byId->assertOk();
    expect(array_column($byId->json('data'), 'package_id'))->toBe(['vendor/cinema']);
});

it('uses the highest SemVer release when a package has no stable release', function (): void {
    $package = storeCatalogPackage('plugin', 'vendor/preview', 'Preview Plugin');
    storeCatalogRelease($package, '1.0.0-beta.1', 'beta');
    storeCatalogRelease($package, '1.0.0-dev.2', 'dev');
    storeCatalogRelease($package, '1.0.0-beta.2', 'beta');

    $response = $this->getJson('/api/v1/packages');

    $response->assertOk()
        ->assertJsonPath('data.0.latest_release.version', '1.0.0-beta.2')
        ->assertJsonPath('data.0.latest_release.channel', 'beta');
});

it('returns a stable cursor snapshot and rejects altered filters or tampering', function (): void {
    foreach (range(1, 5) as $index) {
        $package = storeCatalogPackage(
            'extension',
            sprintf('vendor/package-%d', $index),
            sprintf('Package %d', $index),
        );
        storeCatalogRelease($package, '1.0.0', 'stable');
    }

    $filters = ['q' => 'package', 'per_page' => 2];
    $firstPage = $this->getJson('/api/v1/packages?' . http_build_query($filters));
    $firstPage->assertOk();
    $cursor = $firstPage->json('meta.next_cursor');
    expect($cursor)->toBeString()->not->toBe('');

    $newPackage = storeCatalogPackage('extension', 'vendor/package-new', 'Package New');
    storeCatalogRelease($newPackage, '1.0.0', 'stable');

    $secondPage = $this->getJson('/api/v1/packages?' . http_build_query($filters + ['cursor' => $cursor]));
    $secondPage->assertOk();
    expect(array_column($secondPage->json('data'), 'package_id'))->toBe([
        'vendor/package-3',
        'vendor/package-4',
    ]);

    $thirdPage = $this->getJson('/api/v1/packages?' . http_build_query([
        ...$filters,
        'cursor' => $secondPage->json('meta.next_cursor'),
    ]));
    $thirdPage->assertOk()
        ->assertJsonPath('meta.has_more', false)
        ->assertJsonPath('meta.next_cursor', null);
    expect(array_column($thirdPage->json('data'), 'package_id'))->toBe(['vendor/package-5']);

    $changedFilters = $this->getJson('/api/v1/packages?' . http_build_query([
        'q' => 'package',
        'package_type' => 'theme',
        'per_page' => 2,
        'cursor' => $cursor,
    ]));
    expectRegistryPackageProblem($changedFilters, 400, 'invalid_cursor');

    $tamperedCursor = substr($cursor, 0, -1) . (substr($cursor, -1) === 'A' ? 'B' : 'A');
    $tampered = $this->getJson('/api/v1/packages?' . http_build_query([
        ...$filters,
        'cursor' => $tamperedCursor,
    ]));
    expectRegistryPackageProblem($tampered, 400, 'invalid_cursor');
});

it('validates catalog filters and pagination boundaries', function (): void {
    $invalidQueries = [
        ['package_type' => 'unknown'],
        ['q' => ['nested' => 'value']],
        ['q' => str_repeat('x', 101)],
        ['per_page' => '0'],
        ['per_page' => '101'],
        ['per_page' => '1.5'],
        ['cursor' => str_repeat('x', 2049)],
        ['unexpected' => str_repeat('x', 4100)],
    ];

    foreach ($invalidQueries as $query) {
        $response = $this->getJson('/api/v1/packages?' . http_build_query($query));
        expectRegistryPackageProblem($response, 400, in_array('cursor', array_keys($query), true)
            ? 'invalid_cursor'
            : 'invalid_query');
    }

    foreach ([1, 100] as $perPage) {
        $response = $this->getJson('/api/v1/packages?' . http_build_query(['per_page' => $perPage]));
        $response->assertOk()->assertJsonPath('meta.per_page', $perPage);
    }
});

it('does not list package rows that have no imported releases', function (): void {
    storeCatalogPackage('extension', 'vendor/unpublished', 'Unpublished Package');

    $response = $this->getJson('/api/v1/packages');

    $response->assertOk()->assertJsonPath('data', []);
});

it('returns Registry Problem Details when the shared public IP limit is exceeded', function (): void {
    $ip = '198.51.100.91';
    clearRegistryCatalogLimiter('ip-' . hash('sha256', $ip));

    for ($attempt = 1; $attempt <= 60; $attempt++) {
        $client = 'catalog-rate-test-' . $attempt;
        clearRegistryCatalogLimiter('client-' . hash('sha256', $client));
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders(['X-Pixely-Client' => $client])
            ->getJson('/api/v1/packages')
            ->assertOk();
    }

    $limited = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->withHeaders(['X-Pixely-Client' => 'catalog-rate-test-over-limit'])
        ->getJson('/api/v1/packages');
    expectRegistryPackageProblem($limited, 429, 'rate_limited');
    expect((int) $limited->headers->get('Retry-After'))->toBeGreaterThan(0);
});

function expectRegistryPackageProblem(
    \Illuminate\Testing\TestResponse $response,
    int $status,
    string $code,
): void {
    $response->assertStatus($status)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);

    expect($response->json('code'))->toBe($code)
        ->and($response->json('status'))->toBe($status);
}

function storeCatalogPackage(string $type, string $packageId, string $name): RegistryPackage
{
    return RegistryPackage::query()->create([
        'type' => $type,
        'package_id' => $packageId,
        'name' => $name,
    ]);
}

function clearRegistryCatalogLimiter(string $key): void
{
    RateLimiter::clear(md5('registry-public' . $key));
}

function storeCatalogRelease(RegistryPackage $package, string $version, string $channel): RegistryRelease
{
    $manifest = json_encode([
        'schema' => 'pixely.release-manifest',
        'schema_version' => 1,
        'package' => [
            'type' => $package->type,
            'id' => $package->package_id,
            'name' => $package->name,
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

    return $package->releases()->create([
        'version' => $version,
        'channel' => $channel,
        'published_at' => '2026-01-01T00:00:00Z',
        'package_name' => $package->name,
        'manifest_json' => $manifest,
        'manifest_fingerprint' => hash('sha256', $manifest),
    ]);
}
