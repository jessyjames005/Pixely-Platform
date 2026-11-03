<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\Enums\ArtifactProvider;
use App\Core\Updates\Services\ConfiguredArtifactHostPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfiguredArtifactHostPolicyTest extends TestCase
{
    public function test_allows_only_exact_https_hosts_configured_for_provider(): void
    {
        $policy = new ConfiguredArtifactHostPolicy([
            'github' => ['github.com', 'objects.githubusercontent.com'],
        ]);

        self::assertTrue($policy->allows(ArtifactProvider::GitHub, 'https://github.com/org/repo/releases/download/v1/package.zip'));
        self::assertTrue($policy->allows(ArtifactProvider::GitHub, 'https://objects.githubusercontent.com/package.zip'));
        self::assertFalse($policy->allows(ArtifactProvider::GitHub, 'https://evilgithub.com/package.zip'));
        self::assertFalse($policy->allows(ArtifactProvider::GitHub, 'http://github.com/package.zip'));
        self::assertFalse($policy->allows(ArtifactProvider::GitLab, 'https://github.com/package.zip'));
        self::assertFalse($policy->allows(ArtifactProvider::GitHub, 'https://user:pass@github.com/package.zip'));
    }
}
