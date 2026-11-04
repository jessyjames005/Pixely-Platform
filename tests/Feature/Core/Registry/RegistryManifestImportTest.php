<?php

declare(strict_types=1);

use App\Core\Registry\Exceptions\RegistryImmutableReleaseException;
use App\Core\Registry\Models\RegistryPackage;
use App\Core\Registry\Models\RegistryRelease;
use App\Core\Registry\Services\RegistryManifestImportService;
use App\Core\Updates\Enums\UpdateTargetType;
use App\Core\Updates\Services\ReleaseManifestCanonicalizer;
use App\Core\Updates\Services\SodiumReleaseManifestVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $keyPair = sodium_crypto_sign_keypair();
    $this->registrySecretKey = sodium_crypto_sign_secretkey($keyPair);

    config([
        'update-manager.signing_public_keys' => [
            'registry-test-key' => base64_encode(sodium_crypto_sign_publickey($keyPair)),
        ],
    ]);
    app()->forgetInstance(SodiumReleaseManifestVerifier::class);
});

it('imports trusted manifests for every supported package type with relational release data', function (): void {
    foreach (UpdateTargetType::cases() as $type) {
        $name = sprintf('%s package', ucfirst($type->value));
        $manifest = signedRegistryManifest($this->registrySecretKey, [
            'package' => [
                'type' => $type->value,
                'id' => sprintf('test/%s', $type->value),
                'name' => $name,
            ],
            'compatibility' => [
                ($type === UpdateTargetType::Platform ? 'php' : 'pixely') => '^1.0',
            ],
        ]);
        try {
            $imported = app(RegistryManifestImportService::class)->import($manifest);
        } catch (Throwable $exception) {
            $message = sprintf('%s package import failed: %s', $type->value, $exception->getMessage());
            throw new RuntimeException($message, previous: $exception);
        }

        expect($imported)->toBeTrue();

        $package = RegistryPackage::query()
            ->where('type', $type->value)
            ->where('package_id', sprintf('test/%s', $type->value))
            ->firstOrFail();
        $release = $package->releases()->firstOrFail();

        expect($package->name)->toBe($name)
            ->and($release->version)->toBe('1.0.0')
            ->and($release->channel)->toBe('stable')
            ->and($release->manifest_json)->toBe($manifest)
            ->and($release->package->is($package))->toBeTrue();
    }

    expect(RegistryPackage::query()->count())->toBe(count(UpdateTargetType::cases()))
        ->and(RegistryRelease::query()->count())->toBe(count(UpdateTargetType::cases()));
});

it('treats a repeat or reordered copy of the same signed manifest as a no-op', function (): void {
    $manifest = signedRegistryManifest($this->registrySecretKey);
    $reformattedManifest = json_encode(
        json_decode($manifest, false, 512, JSON_THROW_ON_ERROR),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
    );

    $importer = app(RegistryManifestImportService::class);

    expect($importer->import($manifest))->toBeTrue()
        ->and($importer->import($manifest))->toBeFalse()
        ->and($importer->import($reformattedManifest))->toBeFalse()
        ->and(RegistryPackage::query()->count())->toBe(1)
        ->and(RegistryRelease::query()->count())->toBe(1)
        ->and(RegistryRelease::query()->firstOrFail()->manifest_json)->toBe($manifest);
});

it('imports from the local-file Artisan command and reports an existing release', function (): void {
    $manifest = signedRegistryManifest($this->registrySecretKey);
    $path = writeRegistryManifest($manifest);

    try {
        expect(Artisan::call('pixely:registry:import', ['manifest' => $path]))->toBe(0)
            ->and(Artisan::output())->toContain('imported');

        expect(Artisan::call('pixely:registry:import', ['manifest' => $path]))->toBe(0)
            ->and(Artisan::output())->toContain('already present');
    } finally {
        unlink($path);
    }
});

it('rejects duplicate keys, invalid signatures and malformed signed manifests without writing rows', function (): void {
    $valid = signedRegistryManifest($this->registrySecretKey);
    $duplicateKey = str_replace(
        '"name":"Demo Extension"',
        '"name":"Demo Extension","name":"Ambiguous Extension"',
        $valid,
        $replacementCount,
    );

    expect($replacementCount)->toBe(1);
    expect(fn () => app(RegistryManifestImportService::class)->import($duplicateKey))
        ->toThrow(InvalidArgumentException::class, 'duplicate');

    $invalidSignature = json_decode($valid, true, 512, JSON_THROW_ON_ERROR);
    $invalidSignature['signature']['value'] = base64_encode(
        sodium_crypto_sign_detached('not the signed manifest', $this->registrySecretKey),
    );
    $invalidSignatureJson = json_encode($invalidSignature, JSON_THROW_ON_ERROR);

    $verificationException = null;
    try {
        app(RegistryManifestImportService::class)->import($invalidSignatureJson);
    } catch (Throwable $exception) {
        $verificationException = $exception;
    }

    expect($verificationException)->toBeInstanceOf(App\Core\Updates\Exceptions\UpdateVerificationException::class)
        ->and($verificationException->getMessage())->toContain('verification failed');

    $malformedManifest = signedRegistryManifest($this->registrySecretKey, [
        'package' => [
            'type' => 'extension',
            'id' => 'test/invalid',
            'name' => '',
        ],
    ]);

    expect(fn () => app(RegistryManifestImportService::class)->import($malformedManifest))
        ->toThrow(InvalidArgumentException::class, 'package.name');

    expect(RegistryPackage::query()->count())->toBe(0)
        ->and(RegistryRelease::query()->count())->toBe(0);
});

it('rejects changed releases and preserves the newest package name', function (): void {
    $importer = app(RegistryManifestImportService::class);
    $newest = signedRegistryManifest($this->registrySecretKey, [
        'package' => [
            'type' => 'extension',
            'id' => 'test/immutable',
            'name' => 'Current Name',
        ],
        'release' => [
            'version' => '2.0.0',
            'channel' => 'stable',
            'published_at' => '2026-03-01T00:00:00Z',
            'release_notes' => null,
        ],
    ]);
    $importer->import($newest);

    $changedVersion = signedRegistryManifest($this->registrySecretKey, [
        'package' => [
            'type' => 'extension',
            'id' => 'test/immutable',
            'name' => 'Changed Name',
        ],
        'release' => [
            'version' => '2.0.0',
            'channel' => 'stable',
            'published_at' => '2026-03-01T00:00:00Z',
            'release_notes' => null,
        ],
        'artifact' => [
            'provider' => 'pixely',
            'uri' => 'https://updates.pixely.example/changed.zip',
            'filename' => 'changed.zip',
            'size' => 1024,
            'sha256' => str_repeat('b', 64),
            'signature' => [
                'algorithm' => 'Ed25519',
                'key_id' => 'registry-test-key',
                'value' => base64_encode(sodium_crypto_sign_detached('changed artifact', $this->registrySecretKey)),
            ],
        ],
    ]);

    expect(fn () => $importer->import($changedVersion))
        ->toThrow(RegistryImmutableReleaseException::class, 'already published');

    $older = signedRegistryManifest($this->registrySecretKey, [
        'package' => [
            'type' => 'extension',
            'id' => 'test/immutable',
            'name' => 'Old Name',
        ],
        'release' => [
            'version' => '1.0.0',
            'channel' => 'stable',
            'published_at' => '2026-02-01T00:00:00Z',
            'release_notes' => null,
        ],
    ]);
    expect($importer->import($older))->toBeTrue();

    $package = RegistryPackage::query()->firstOrFail();
    expect($package->name)->toBe('Current Name')
        ->and($package->releases()->count())->toBe(2)
        ->and($package->releases()->where('version', '2.0.0')->firstOrFail()->manifest_json)->toBe($newest);
});

it('fails clearly when the import file cannot be read', function (): void {
    expect(Artisan::call('pixely:registry:import', [
        'manifest' => storage_path('framework/testing/nonexistent-registry-manifest.json'),
    ]))->toBe(1)
        ->and(Artisan::output())->toContain('Unable to read manifest file');
});

it('rolls back a newly created package if release persistence fails', function (): void {
    RegistryRelease::creating(static function (): void {
        throw new RuntimeException('Simulated release persistence failure.');
    });

    $exception = null;
    try {
        app(RegistryManifestImportService::class)->import(signedRegistryManifest($this->registrySecretKey));
    } catch (Throwable $thrown) {
        $exception = $thrown;
    } finally {
        RegistryRelease::flushEventListeners();
    }

    expect($exception)->toBeInstanceOf(RuntimeException::class)
        ->and(RegistryPackage::query()->count())->toBe(0)
        ->and(RegistryRelease::query()->count())->toBe(0);
});

/**
 * @param array<string, mixed> $overrides
 */
function signedRegistryManifest(string $secretKey, array $overrides = []): string
{
    $manifest = [
        'schema' => 'pixely.release-manifest',
        'schema_version' => 1,
        'package' => [
            'type' => 'extension',
            'id' => 'test/demo',
            'name' => 'Demo Extension',
        ],
        'release' => [
            'version' => '1.0.0',
            'channel' => 'stable',
            'published_at' => '2026-01-01T00:00:00Z',
            'release_notes' => null,
        ],
        'compatibility' => [
            'pixely' => '^1.0',
        ],
        'dependencies' => [
            'requires' => [],
            'conflicts' => [],
        ],
        'artifact' => [
            'provider' => 'pixely',
            'uri' => 'https://updates.pixely.example/demo.zip',
            'filename' => 'demo.zip',
            'size' => 1024,
            'sha256' => str_repeat('a', 64),
            'signature' => [
                'algorithm' => 'Ed25519',
                'key_id' => 'registry-test-key',
                'value' => base64_encode(sodium_crypto_sign_detached('demo artifact', $secretKey)),
            ],
        ],
        'signature' => [
            'algorithm' => 'Ed25519',
            'key_id' => 'registry-test-key',
            'value' => '',
        ],
    ];

    foreach ($overrides as $key => $value) {
        $manifest[$key] = $value;
    }

    $canonicalizer = app(ReleaseManifestCanonicalizer::class);
    $object = json_decode(json_encode($manifest, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
    $canonical = $canonicalizer->canonicalizeUnsignedManifest($object);
    $manifest['signature']['value'] = base64_encode(sodium_crypto_sign_detached($canonical, $secretKey));

    return json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

function writeRegistryManifest(string $manifest): string
{
    $directory = storage_path('framework/testing');
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $path = $directory . DIRECTORY_SEPARATOR . 'registry-manifest-' . bin2hex(random_bytes(8)) . '.json';
    file_put_contents($path, $manifest);

    return $path;
}
