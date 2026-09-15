<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Manager;

use App\Core\Extensions\Dependency\ExtensionDependencyResolver;
use App\Core\Extensions\Discovery\ExtensionDiscoverer;
use App\Core\Extensions\Discovery\ExtensionManifestReader;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Manager\ExtensionManager;
use App\Core\Extensions\Registry\ExtensionRegistry;
use App\Core\Extensions\Repositories\InMemoryExtensionStateRepository;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\Extensions\FakeExtension;

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
        $manager = $this->createManager();

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
        $manager = $this->createManager();

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
        $manager = $this->createManager();

        $manager->load(
            dirname(__DIR__, 5) . '/tests/Fixtures/Extensions'
        );

        $this->assertArrayHasKey(
            'gallery',
            $manager->all()
        );
    }

    public function test_it_can_find_an_extension_state(): void
    {
        $manager = $this->createManager();

        $manager->register(new FakeExtension());

        $state = $manager->findState('gallery');

        $this->assertNotNull($state);

        $this->assertSame(
            'gallery',
            $state->extension->manifest()->id,
        );
    }

    public function test_it_knows_when_an_extension_is_enabled(): void
    {
        $manager = $this->createManager();

        $manager->register(new FakeExtension());

        $this->assertTrue(
            $manager->isEnabled('gallery')
        );
    }

    /**
     * Create a new extension manager.
     */
    private function createManager(): ExtensionManager
    {
        return new ExtensionManager(
            new ExtensionRegistry(),
            new ExtensionRepository(
                new ExtensionDiscoverer(),
                new ExtensionManifestReader(),
                new ExtensionDependencyResolver(),
            ),
            new InMemoryExtensionStateRepository(),
        );
    }

    public function test_it_returns_enabled_extensions(): void
    {
        $manager = $this->createManager();

        $manager->register(new FakeExtension());

        $this->assertCount(
            1,
            $manager->enabled(),
        );
    }

    public function test_it_returns_no_disabled_extensions(): void
    {
        $manager = $this->createManager();

        $manager->register(new FakeExtension());

        $this->assertCount(
            0,
            $manager->disabled(),
        );
    }

    public function test_it_can_disable_an_extension(): void
    {
        $manager = $this->createManager();

        $manager->register(new FakeExtension());

        $manager->disable('gallery');

        $this->assertFalse(
            $manager->isEnabled('gallery')
        );
    }

    public function test_it_can_enable_an_extension(): void
    {
        $manager = $this->createManager();

        $manager->register(new FakeExtension());

        $manager->disable('gallery');

        $manager->enable('gallery');

        $this->assertTrue(
            $manager->isEnabled('gallery')
        );
    }

    /**
     * Regression test: PHP has no long-running process state, so every
     * request re-discovers and re-registers every extension from
     * scratch. register() used to unconditionally reset the persisted
     * status back to Enabled, which silently undid any disable() the
     * moment the next request's registration ran — before that
     * request's own code could ever observe the disabled state.
     */
    public function test_disabling_an_extension_survives_a_fresh_registration(): void
    {
        $sharedState = new InMemoryExtensionStateRepository();

        $firstRequestManager = new ExtensionManager(
            new ExtensionRegistry(),
            new ExtensionRepository(
                new ExtensionDiscoverer(),
                new ExtensionManifestReader(),
                new ExtensionDependencyResolver(),
            ),
            $sharedState,
        );
        $firstRequestManager->register(new FakeExtension());
        $firstRequestManager->disable('gallery');

        // A new manager instance over the same persisted state,
        // re-registering the extension — exactly what happens on the
        // next HTTP request.
        $secondRequestManager = new ExtensionManager(
            new ExtensionRegistry(),
            new ExtensionRepository(
                new ExtensionDiscoverer(),
                new ExtensionManifestReader(),
                new ExtensionDependencyResolver(),
            ),
            $sharedState,
        );
        $secondRequestManager->register(new FakeExtension());

        $this->assertFalse(
            $secondRequestManager->isEnabled('gallery'),
        );
    }
}
