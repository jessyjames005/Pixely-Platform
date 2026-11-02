<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\DTO\UpdateCandidate;
use App\Core\Updates\Enums\ArtifactProvider;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;
use App\Core\Updates\Services\ExtensionUpdateDiscovery;
use App\Core\Updates\Services\RuntimeCompatibilityChecker;
use Illuminate\Foundation\Application;
use Tests\TestCase;

final class RuntimeCompatibilityCheckerTest extends TestCase
{
    public function test_it_reports_the_actual_runtime_versions(): void
    {
        $versions = (new RuntimeCompatibilityChecker())->runtimeVersions();

        self::assertSame(PHP_VERSION, $versions['php']);
        self::assertSame(Application::VERSION, $versions['laravel']);
        self::assertSame((string) config('pixely.kernel_version'), $versions['pixely']);
    }

    public function test_update_discovery_is_registered_with_runtime_compatibility_services(): void
    {
        self::assertInstanceOf(ExtensionUpdateDiscovery::class, $this->app->make(ExtensionUpdateDiscovery::class));
    }

    public function test_it_requires_every_declared_runtime_constraint_to_match(): void
    {
        $checker = new RuntimeCompatibilityChecker();
        $versions = ['php' => '8.3.14', 'laravel' => '13.8.0', 'pixely' => '1.0.0'];
        $constraints = ['php' => '>=8.3', 'laravel' => '>=13.0', 'pixely' => '>=1.0.0 <2.0.0'];

        self::assertTrue($checker->supports($this->candidate($constraints), $versions));

        $incompatibleConstraints = [
            'php' => '>=9.0',
            'laravel' => '>=14.0',
            'pixely' => '>=2.0.0',
        ];

        foreach ($incompatibleConstraints as $runtime => $incompatibleConstraint) {
            $incompatible = $constraints;
            $incompatible[$runtime] = $incompatibleConstraint;

            self::assertFalse($checker->supports($this->candidate($incompatible), $versions));
        }
    }

    public function test_it_rejects_invalid_unknown_and_unavailable_runtime_constraints(): void
    {
        $checker = new RuntimeCompatibilityChecker();
        $versions = ['php' => '8.3.14', 'laravel' => '13.8.0', 'pixely' => '1.0.0'];

        self::assertFalse($checker->supports($this->candidate([]), $versions));
        self::assertFalse($checker->supports($this->candidate(['php' => '>=not-a-version']), $versions));
        self::assertFalse($checker->supports(
            $this->candidate(['unknown-runtime' => '>=1.0.0']),
            $versions + ['unknown-runtime' => '99.0.0'],
        ));
        self::assertFalse($checker->supports($this->candidate(['laravel' => '>=13.0']), ['php' => '8.3.14']));
    }

    /** @param array<string, string> $runtimeConstraints */
    private function candidate(array $runtimeConstraints): UpdateCandidate
    {
        return new UpdateCandidate(
            UpdateTargetType::Extension,
            'pixely/gallery',
            'Gallery',
            '1.2.0',
            UpdateChannel::Stable,
            new ArtifactReference(
                ArtifactProvider::Pixely,
                'https://registry.pixely.test/artifacts/gallery.zip',
                str_repeat('a', 64),
            ),
            runtimeConstraints: $runtimeConstraints,
        );
    }
}
