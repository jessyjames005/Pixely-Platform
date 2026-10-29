<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\Enums\ArtifactProvider;
use PHPUnit\Framework\TestCase;

final class ArtifactReferenceTest extends TestCase
{
    public function test_it_accepts_a_valid_artifact_reference(): void
    {
        $artifact = new ArtifactReference(
            provider: ArtifactProvider::GitHub,
            uri: 'https://github.com/pixely/gallery/releases/download/v1.2.0/gallery.zip',
            sha256: str_repeat('a', 64),
        );

        $this->assertSame(ArtifactProvider::GitHub, $artifact->provider);
        $this->assertSame(str_repeat('a', 64), $artifact->sha256);
    }

    public function test_it_rejects_an_invalid_checksum(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ArtifactReference(
            provider: ArtifactProvider::Pixely,
            uri: 'https://updates.pixely.example/releases/gallery.zip',
            sha256: 'not-a-checksum',
        );
    }

    public function test_it_requires_signature_and_key_identifier_together(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ArtifactReference(
            provider: ArtifactProvider::Pixely,
            uri: 'https://updates.pixely.example/releases/gallery.zip',
            sha256: str_repeat('b', 64),
            signature: 'signature-value',
        );
    }
}
