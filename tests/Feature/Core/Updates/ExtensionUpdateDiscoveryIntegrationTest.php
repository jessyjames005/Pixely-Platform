<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Updates;

use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use App\Core\Updates\DTO\InstalledExtension;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Exceptions\RegistryApiException;
use App\Core\Updates\Services\ExtensionUpdateDiscovery;
use App\Core\Updates\Services\HttpUpdateRegistry;
use App\Core\Updates\Services\JsonObjectKeyUniquenessGuard;
use App\Core\Updates\Services\ReleaseManifestCanonicalizer;
use App\Core\Updates\Services\SodiumReleaseManifestVerifier;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ExtensionUpdateDiscoveryIntegrationTest extends TestCase
{
    private const REGISTRY_URL = 'https://registry.pixely.test';

    /** @var array{0: string, 1: string} */
    private array $keys;

    protected function setUp(): void
    {
        parent::setUp();

        if (!function_exists('sodium_crypto_sign_keypair')) {
            self::markTestSkipped('The sodium extension is required.');
        }

        $pair = sodium_crypto_sign_keypair();
        $this->keys = [
            base64_encode(sodium_crypto_sign_secretkey($pair)),
            base64_encode(sodium_crypto_sign_publickey($pair)),
        ];
    }

    public function testItProposesTheHighestCompatibleSignedReleaseAndSendsRuntimeContext(): void
    {
        $compatible = $this->signedManifest('1.2.0');
        $incompatible = $this->signedManifest('1.3.0', ['php' => '>=999.0']);

        Http::fake([
            self::REGISTRY_URL . '/api/v1/releases*' => Http::response([
                'data' => [
                    ['manifest' => $compatible],
                    ['manifest' => $incompatible],
                ],
                'meta' => ['per_page' => 20, 'next_cursor' => null, 'has_more' => false],
            ]),
        ]);

        $result = $this->discovery()->check('gallery', UpdateChannel::Stable);

        self::assertNotNull($result);
        self::assertSame('1.2.0', $result->candidate?->version);
        Http::assertSent(fn ($request): bool => parse_url($request->url(), PHP_URL_PATH) === '/api/v1/releases'
            && $request['package_id'] === 'pixely/gallery'
            && $request['channel'] === 'stable'
            && ($request['runtime']['php'] ?? null) === PHP_VERSION
            && ($request['runtime']['laravel'] ?? null) === Application::VERSION
            && ($request['runtime']['pixely'] ?? null) === (string) config('pixely.kernel_version'));
        Http::assertSentCount(1);
    }

    public function testItReturnsNoCandidateWhenOnlySignedReleasesAreIncompatible(): void
    {
        $incompatible = $this->signedManifest('1.2.0', ['php' => '>=999.0']);

        Http::fake([
            self::REGISTRY_URL . '/api/v1/releases*' => Http::response([
                'data' => [['manifest' => $incompatible]],
                'meta' => ['per_page' => 20, 'next_cursor' => null, 'has_more' => false],
            ]),
        ]);

        $result = $this->discovery()->check('gallery');

        self::assertNotNull($result);
        self::assertNull($result->candidate);
        self::assertFalse($result->hasUpdate());
        Http::assertSentCount(1);
    }

    public function testItPropagatesRegistryHttpFailuresThroughDiscovery(): void
    {
        Http::fake([
            self::REGISTRY_URL . '/api/v1/releases*' => Http::response([
                'type' => 'about:blank',
                'title' => 'Unavailable',
                'status' => 503,
            ], 503),
        ]);

        $this->expectException(RegistryApiException::class);

        $this->discovery()->check('gallery');
    }

    private function discovery(): ExtensionUpdateDiscovery
    {
        $canonicalizer = new ReleaseManifestCanonicalizer();
        $registry = new HttpUpdateRegistry(
            self::REGISTRY_URL,
            5,
            20,
            new JsonObjectKeyUniquenessGuard(),
            new SodiumReleaseManifestVerifier($canonicalizer, ['registry-test' => $this->keys[1]]),
        );

        return new ExtensionUpdateDiscovery($this->inventory(), $registry);
    }

    private function inventory(): InstalledExtensionInventoryInterface
    {
        $installed = new InstalledExtension(
            'gallery',
            'pixely/gallery',
            'Gallery',
            '1.0.0',
            '1.0.0',
            '/app/Extensions/Gallery',
        );

        return new class ($installed) implements InstalledExtensionInventoryInterface {
            public function __construct(private InstalledExtension $extension)
            {
            }

            public function all(): array
            {
                return [$this->extension];
            }

            public function find(string $extensionId): ?InstalledExtension
            {
                return $extensionId === $this->extension->id ? $this->extension : null;
            }
        };
    }

    /**
     * @param array<string, string> $runtimeConstraints
     * @return array<string, mixed>
     */
    private function signedManifest(string $version, array $runtimeConstraints = []): array
    {
        $manifest = [
            'schema' => 'pixely.release-manifest',
            'schema_version' => 1,
            'package' => ['type' => 'extension', 'id' => 'pixely/gallery', 'name' => 'Gallery'],
            'release' => [
                'version' => $version,
                'channel' => 'stable',
                'published_at' => '2026-10-30T12:00:00Z',
                'release_notes' => self::REGISTRY_URL . '/releases/gallery/' . $version,
            ],
            'compatibility' => array_replace([
                'php' => '*',
                'laravel' => '*',
                'pixely' => '*',
            ], $runtimeConstraints),
            'dependencies' => ['requires' => [], 'conflicts' => []],
            'artifact' => [
                'provider' => 'pixely',
                'uri' => 'https://updates.pixely.test/gallery-' . $version . '.zip',
                'filename' => 'gallery-' . $version . '.zip',
                'size' => 1024,
                'sha256' => str_repeat('a', 64),
                'signature' => [
                    'algorithm' => 'Ed25519',
                    'key_id' => 'artifact-test',
                    'value' => base64_encode(str_repeat("\x01", SODIUM_CRYPTO_SIGN_BYTES)),
                ],
            ],
            'signature' => [
                'algorithm' => 'Ed25519',
                'key_id' => 'registry-test',
                'value' => base64_encode(str_repeat("\0", SODIUM_CRYPTO_SIGN_BYTES)),
            ],
        ];

        $unsigned = json_decode(json_encode($manifest, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $message = (new ReleaseManifestCanonicalizer())->canonicalizeUnsignedManifest($unsigned);
        $secretKey = base64_decode($this->keys[0], true);
        $manifest['signature']['value'] = base64_encode(sodium_crypto_sign_detached($message, $secretKey));

        return $manifest;
    }
}
