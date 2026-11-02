<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\DTO\ReleaseManifest;
use App\Core\Updates\Enums\ArtifactProvider;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;
use PHPUnit\Framework\TestCase;

final class ReleaseManifestTest extends TestCase
{
    public function test_it_builds_a_typed_manifest_from_valid_data(): void
    {
        $manifest = ReleaseManifest::fromArray($this->validManifest());

        $this->assertInstanceOf(ReleaseManifest::class, $manifest);
        $this->assertSame(UpdateTargetType::Extension, $manifest->package->type);
        $this->assertSame('pixely/gallery', $manifest->package->id);
        $this->assertSame('1.2.0', $manifest->release->version);
        $this->assertSame(UpdateChannel::Stable, $manifest->release->channel);
        $this->assertSame(ArtifactProvider::GitHub, $manifest->artifact->provider);
        $this->assertSame('pixely-manifest-2026-01', $manifest->signature->keyId);
        $this->assertSame(['pixely/media' => '^1.0'], $manifest->dependencies->requires);
    }

    public function test_it_rejects_unknown_root_fields(): void
    {
        $data = $this->validManifest();
        $data['untrusted'] = true;

        $this->expectException(\InvalidArgumentException::class);
        ReleaseManifest::fromArray($data);
    }

    public function test_it_rejects_an_unsupported_schema_version(): void
    {
        $data = $this->validManifest();
        $data['schema_version'] = 2;

        $this->expectException(\InvalidArgumentException::class);
        ReleaseManifest::fromArray($data);
    }

    public function test_it_requires_runtime_compatibility_for_the_package_type(): void
    {
        $data = $this->validManifest();
        $data['compatibility'] = ['php' => '>=8.3'];

        $this->expectException(\InvalidArgumentException::class);
        ReleaseManifest::fromArray($data);
    }

    public function test_it_rejects_non_string_runtime_compatibility_values(): void
    {
        $data = $this->validManifest();
        $data['compatibility']['pixely'] = 1;

        $this->expectException(\InvalidArgumentException::class);
        ReleaseManifest::fromArray($data);
    }

    public function test_it_rejects_non_canonical_artifact_checksums(): void
    {
        $data = $this->validManifest();
        $data['artifact']['sha256'] = strtoupper(str_repeat('a', 64));

        $this->expectException(\InvalidArgumentException::class);
        ReleaseManifest::fromArray($data);
    }

    public function test_it_rejects_signatures_that_are_not_64_bytes(): void
    {
        $data = $this->validManifest();
        $data['signature']['value'] = base64_encode('too-short');

        $this->expectException(\InvalidArgumentException::class);
        ReleaseManifest::fromArray($data);
    }

    /** @return array<string, mixed> */
    private function validManifest(): array
    {
        $signature = base64_encode(str_repeat('s', 64));

        return [
            'schema' => ReleaseManifest::SCHEMA,
            'schema_version' => ReleaseManifest::SCHEMA_VERSION,
            'package' => [
                'type' => 'extension',
                'id' => 'pixely/gallery',
                'name' => 'Gallery',
            ],
            'release' => [
                'version' => '1.2.0',
                'channel' => 'stable',
                'published_at' => '2026-10-30T12:00:00Z',
                'release_notes' => 'https://registry.pixely.example/releases/gallery/1.2.0',
            ],
            'compatibility' => [
                'php' => '>=8.3',
                'pixely' => '>=1.0.0 <2.0.0',
            ],
            'dependencies' => [
                'requires' => ['pixely/media' => '^1.0'],
                'conflicts' => [],
            ],
            'artifact' => [
                'provider' => 'github',
                'uri' => 'https://github.com/pixely/gallery/releases/download/1.2.0/gallery.zip',
                'filename' => 'gallery-1.2.0.zip',
                'size' => 245760,
                'sha256' => str_repeat('a', 64),
                'signature' => [
                    'algorithm' => 'Ed25519',
                    'key_id' => 'pixely-artifact-2026-01',
                    'value' => $signature,
                ],
            ],
            'signature' => [
                'algorithm' => 'Ed25519',
                'key_id' => 'pixely-manifest-2026-01',
                'value' => $signature,
            ],
        ];
    }
}
