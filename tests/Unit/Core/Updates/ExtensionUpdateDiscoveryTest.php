<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Updates;

use App\Core\Updates\Contracts\InstalledExtensionInventoryInterface;
use App\Core\Updates\Contracts\UpdateRegistryInterface;
use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\DTO\ExtensionUpdateAvailability;
use App\Core\Updates\DTO\InstalledExtension;
use App\Core\Updates\DTO\UpdateCandidate;
use App\Core\Updates\DTO\UpdateQuery;
use App\Core\Updates\Enums\ArtifactProvider;
use App\Core\Updates\Enums\UpdateChannel;
use App\Core\Updates\Enums\UpdateTargetType;
use App\Core\Updates\Services\ExtensionUpdateDiscovery;
use PHPUnit\Framework\TestCase;

final class ExtensionUpdateDiscoveryTest extends TestCase
{
    public function test_it_selects_the_highest_newer_release_for_the_exact_package_and_channel(): void
    {
        $installed = $this->installed();
        $inventory = new class($installed) implements InstalledExtensionInventoryInterface {
            public function __construct(private InstalledExtension $item) {}
            public function all(): array { return [$this->item]; }
            public function find(string $extensionId): ?InstalledExtension { return $extensionId === $this->item->id ? $this->item : null; }
        };

        $registry = new class implements UpdateRegistryInterface {
            public function findUpdates(UpdateQuery $query): iterable
            {
                \PHPUnit\Framework\Assert::assertSame('pixely/gallery', $query->packageId);
                return [
                    $this->candidate('1.3.0'),
                    $this->candidate('1.2.0'),
                    new UpdateCandidate(UpdateTargetType::Extension, 'other/gallery', 'Other', '9.0.0', UpdateChannel::Stable, $this->artifact()),
                    new UpdateCandidate(UpdateTargetType::Extension, 'pixely/gallery', 'Gallery', '2.0.0-beta.1', UpdateChannel::Beta, $this->artifact()),
                ];
            }
            public function findRelease(string $packageId, string $version): ?UpdateCandidate { return null; }
            private function candidate(string $version): UpdateCandidate
            {
                return new UpdateCandidate(UpdateTargetType::Extension, 'pixely/gallery', 'Gallery', $version, UpdateChannel::Stable, self::artifact());
            }
            private function artifact(): ArtifactReference
            {
                return new ArtifactReference(ArtifactProvider::Pixely, 'https://registry.pixely.test/artifacts/gallery.zip', str_repeat('a', 64));
            }
        };

        $result = (new ExtensionUpdateDiscovery($inventory, $registry))->checkAll();

        self::assertCount(1, $result);
        self::assertInstanceOf(ExtensionUpdateAvailability::class, $result[0]);
        self::assertSame('1.3.0', $result[0]->candidate?->version);
    }

    public function test_it_returns_no_update_when_registry_has_no_newer_release(): void
    {
        $installed = $this->installed();
        $inventory = new class($installed) implements InstalledExtensionInventoryInterface {
            public function __construct(private InstalledExtension $item) {}
            public function all(): array { return [$this->item]; }
            public function find(string $extensionId): ?InstalledExtension { return $this->item; }
        };
        $registry = new class implements UpdateRegistryInterface {
            public function findUpdates(UpdateQuery $query): iterable { return []; }
            public function findRelease(string $packageId, string $version): ?UpdateCandidate { return null; }
        };

        $result = (new ExtensionUpdateDiscovery($inventory, $registry))->check('gallery');

        self::assertNotNull($result);
        self::assertFalse($result->hasUpdate());
    }

    private function installed(): InstalledExtension
    {
        return new InstalledExtension('gallery', 'pixely/gallery', 'Gallery', '1.1.0', '1.0.0', '/app/Extensions/Gallery');
    }
}
