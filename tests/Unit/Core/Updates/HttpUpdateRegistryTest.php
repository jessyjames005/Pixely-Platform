<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\DTO\UpdateQuery;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;
use App\Core\Updates\Exceptions\RegistryApiException;
use App\Core\Updates\Services\HttpUpdateRegistry;
use App\Core\Updates\Services\JsonObjectKeyUniquenessGuard;
use App\Core\Updates\Services\ReleaseManifestCanonicalizer;
use App\Core\Updates\Services\SodiumReleaseManifestVerifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class HttpUpdateRegistryTest extends TestCase
{
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

    public function test_it_verifies_and_maps_signed_manifests_from_the_release_list(): void
    {
        $manifest = $this->signedManifest();
        Http::fake([
            'registry.pixely.test/api/v1/releases*' => Http::response([
                'data' => [['manifest' => $manifest]],
                'meta' => ['per_page' => 20, 'next_cursor' => null, 'has_more' => false],
            ]),
        ]);

        $results = $this->registry()->findUpdates(new UpdateQuery(
            type: UpdateTargetType::Extension,
            packageId: 'pixely/gallery',
            installedVersion: '1.0.0',
            channel: UpdateChannel::Stable,
            runtimeVersions: ['php' => '8.3.0', 'pixely' => '1.0.0'],
        ));

        self::assertCount(1, $results);
        self::assertSame('pixely/gallery', $results[0]->packageId);
        self::assertSame('1.2.0', $results[0]->version);
        self::assertSame('1.0.0', $results[0]->runtimeConstraints['pixely']);
        self::assertSame('https://registry.pixely.test/releases/gallery/1.2.0', $results[0]->releaseNotes);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://registry.pixely.test/api/v1/releases'
            && $request['package_type'] === 'extension'
            && $request['package_id'] === 'pixely/gallery'
            && $request['channel'] === 'stable'
            && $request['after_version'] === '1.0.0');
    }

    public function test_it_rejects_a_manifest_whose_signed_metadata_was_changed(): void
    {
        $manifest = $this->signedManifest();
        $manifest['package']['name'] = 'Tampered';
        Http::fake([
            'registry.pixely.test/api/v1/releases*' => Http::response([
                'data' => [['manifest' => $manifest]],
                'meta' => ['per_page' => 20, 'next_cursor' => null, 'has_more' => false],
            ]),
        ]);

        $this->expectException(RegistryApiException::class);

        $this->registry()->findUpdates(new UpdateQuery(
            type: UpdateTargetType::Extension,
            packageId: 'pixely/gallery',
            installedVersion: '1.0.0',
        ));
    }

    public function test_it_requires_an_exact_package_type_and_identity_for_release_resolution(): void
    {
        $manifest = $this->signedManifest();
        Http::fake([
            'registry.pixely.test/api/v1/releases/resolve*' => Http::response([
                'data' => ['manifest' => $manifest],
            ]),
        ]);

        $candidate = $this->registry()->findRelease(
            UpdateTargetType::Extension,
            'pixely/gallery',
            '1.2.0',
        );

        self::assertNotNull($candidate);
        self::assertSame(UpdateTargetType::Extension, $candidate->type);
        self::assertSame('1.2.0', $candidate->version);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://registry.pixely.test/api/v1/releases/resolve'
            && $request['package_type'] === 'extension');
    }

    public function test_it_rejects_duplicate_json_object_member_names(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new JsonObjectKeyUniquenessGuard())->assertUniqueObjectKeys('{"data":{"version":"1.0.0","version":"2.0.0"}}');
    }

    public function test_it_follows_cursor_pages_and_rejects_repeated_cursors(): void
    {
        $manifest = $this->signedManifest();
        Http::fakeSequence()
            ->push([
                'data' => [['manifest' => $manifest]],
                'meta' => ['per_page' => 20, 'next_cursor' => 'page-2', 'has_more' => true],
            ])
            ->push([
                'data' => [],
                'meta' => ['per_page' => 20, 'next_cursor' => null, 'has_more' => false],
            ]);

        $results = $this->registry()->findUpdates(new UpdateQuery(
            type: UpdateTargetType::Extension,
            packageId: 'pixely/gallery',
            installedVersion: '1.0.0',
        ));

        self::assertCount(1, $results);
        Http::assertSentCount(2);
    }

    private function registry(): HttpUpdateRegistry
    {
        $canonicalizer = new ReleaseManifestCanonicalizer();

        return new HttpUpdateRegistry(
            'https://registry.pixely.test',
            5,
            20,
            new JsonObjectKeyUniquenessGuard(),
            $canonicalizer,
            new SodiumReleaseManifestVerifier($canonicalizer, ['registry-2026' => $this->keys[1]]),
        );
    }

    /** @return array<string, mixed> */
    private function signedManifest(): array
    {
        $artifactSignature = base64_encode(str_repeat("\x01", SODIUM_CRYPTO_SIGN_BYTES));
        $manifest = [
            'schema' => 'pixely.release-manifest',
            'schema_version' => 1,
            'package' => ['type' => 'extension', 'id' => 'pixely/gallery', 'name' => 'Gallery'],
            'release' => [
                'version' => '1.2.0',
                'channel' => 'stable',
                'published_at' => '2026-10-30T12:00:00Z',
                'release_notes' => 'https://registry.pixely.test/releases/gallery/1.2.0',
            ],
            'compatibility' => ['pixely' => '1.0.0'],
            'dependencies' => ['requires' => [], 'conflicts' => []],
            'artifact' => [
                'provider' => 'pixely',
                'uri' => 'https://updates.pixely.test/gallery-1.2.0.zip',
                'filename' => 'gallery-1.2.0.zip',
                'size' => 1024,
                'sha256' => str_repeat('a', 64),
                'signature' => [
                    'algorithm' => 'Ed25519',
                    'key_id' => 'artifact-2026',
                    'value' => $artifactSignature,
                ],
            ],
            'signature' => [
                'algorithm' => 'Ed25519',
                'key_id' => 'registry-2026',
                'value' => base64_encode(str_repeat("\0", SODIUM_CRYPTO_SIGN_BYTES)),
            ],
        ];

        $unsigned = json_decode(json_encode($manifest, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $canonicalizer = new ReleaseManifestCanonicalizer();
        $message = $canonicalizer->canonicalizeUnsignedManifest($unsigned);
        $secretKey = base64_decode($this->keys[0], true);
        $manifest['signature']['value'] = base64_encode(sodium_crypto_sign_detached($message, $secretKey));

        return $manifest;
    }
}
