<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Manager;

use App\Core\Extensions\Registry\ExtensionRegistry;
use App\Core\Extensions\Manager\ExtensionManager;
use Tests\Fakes\Extensions\FakeExtension;
use PHPUnit\Framework\TestCase;
use App\Core\Extensions\Discovery\ExtensionDiscoverer;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Discovery\ExtensionManifestReader;

/**
 * Tests the extension manager.
 */
final class ExtensionManagerTest extends TestCase
{
    /**
     * Ensure an extension can be registered through the manager.
     */
    public function test_it_can_register_an_extension(): void
    {
        $registry = new ExtensionRegistry();

         $repository = new ExtensionRepository(
            new ExtensionDiscoverer(),
            new ExtensionManifestReader()
        );

        $manager = new ExtensionManager(
            $registry,
            $repository
        );

        $extension = new FakeExtension();

        $manager->register($extension);

        $this->assertSame(
            $extension,
            $manager->all()['gallery']
        );
    }

    /**
     * Ensure the manager boots registered extensions.
     */
    public function test_it_can_boot_extensions(): void
    {
        $registry = new ExtensionRegistry();

         $repository = new ExtensionRepository(
            new ExtensionDiscoverer(),
            new ExtensionManifestReader()
        );

        $manager = new ExtensionManager(
            $registry,
            $repository
        );

        $manager->register(
            new FakeExtension()
        );

        $manager->boot();

        $this->assertTrue(true);
    }

    /**
     * Ensure the manager can load extensions from a path.
     */
    public function test_it_can_load_extensions(): void
    {
        $registry = new ExtensionRegistry();

        $repository = new ExtensionRepository(
            new ExtensionDiscoverer(),
            new ExtensionManifestReader()
        );

        $manager = new ExtensionManager(
            $registry,
            $repository
        );

        $manager->load(
            dirname(__DIR__, 5) . '/tests/Fixtures/Extensions'
        );

        $this->assertArrayHasKey(
            'gallery',
            $manager->all()
        );
    }
}
