<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\DTO\UpdateCandidate;
use App\Core\Updates\Enums\ArtifactProvider;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;
use PHPUnit\Framework\TestCase;

final class UpdateCandidateTest extends TestCase
{
    public function test_it_accepts_a_semantic_release_candidate(): void
    {
        $candidate = new UpdateCandidate(
            type: UpdateTargetType::Extension,
            packageId: 'pixely/gallery',
            name: 'Gallery',
            version: '1.2.0',
            channel: UpdateChannel::Stable,
            artifact: new ArtifactReference(
                provider: ArtifactProvider::Pixely,
                uri: 'https://updates.pixely.example/gallery/1.2.0.zip',
                sha256: str_repeat('c', 64),
            ),
            requires: ['pixely/media' => '^1.0'],
        );

        $this->assertSame('pixely/gallery', $candidate->packageId);
        $this->assertSame('1.2.0', $candidate->version);
        $this->assertSame(['pixely/media' => '^1.0'], $candidate->requires);
    }

    public function test_it_rejects_a_non_semantic_version(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UpdateCandidate(
            type: UpdateTargetType::Extension,
            packageId: 'pixely/gallery',
            name: 'Gallery',
            version: 'latest',
            channel: UpdateChannel::Stable,
            artifact: new ArtifactReference(
                provider: ArtifactProvider::Pixely,
                uri: 'https://updates.pixely.example/gallery/latest.zip',
                sha256: str_repeat('d', 64),
            ),
        );
    }
}
