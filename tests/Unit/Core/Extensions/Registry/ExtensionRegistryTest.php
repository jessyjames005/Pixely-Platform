<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Extensions\Registry;

use App\Core\Extensions\Registry\ExtensionRegistry;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\Extensions\FakeExtension;
use App\Core\Extensions\Exceptions\ExtensionAlreadyRegisteredException;
use App\Core\Extensions\Exceptions\ExtensionNotFoundException;

/**
 * Tests the ExtensionRegistry.
 */
final class ExtensionRegistryTest extends TestCase
{
    /**
     * Ensure an extension can be registered.
     */
    public function test_it_can_register_an_extension(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register(
            new FakeExtension()
        );

        $this->assertCount(
            1,
            $registry->all()
        );
    }

    /**
     * Ensure the registry can determine whether an extension is registered.
     */
    public function test_it_can_check_if_an_extension_is_registered(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register(
            new FakeExtension()
        );

        $this->assertTrue(
            $registry->has('gallery')
        );
    }

    /**
     * Ensure the same extension cannot be registered twice.
     */
    public function test_it_cannot_register_the_same_extension_twice(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register(
            new FakeExtension()
        );

        $this->expectException(
            ExtensionAlreadyRegisteredException::class
        );

        $registry->register(
            new FakeExtension()
        );
    }

    /**
     * Ensure a registered extension can be retrieved.
     */
    public function test_it_can_return_a_registered_extension(): void
    {
        $registry = new ExtensionRegistry();

        $extension = new FakeExtension();

        $registry->register($extension);

        $this->assertSame(
            $extension,
            $registry->get('gallery')
        );
    }

    /**
     * Ensure an exception is thrown when an extension is not registered.
     */
    public function test_it_throws_an_exception_when_extension_is_not_found(): void
    {
        $registry = new ExtensionRegistry();

        $this->expectException(
            ExtensionNotFoundException::class
        );

        $registry->get('unknown');
    }

    /**
     * Ensure the registry returns the number of registered extensions.
     */
    public function test_it_can_count_registered_extensions(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register(
            new FakeExtension()
        );

        $this->assertSame(
            1,
            $registry->count()
        );
    }

    /**
     * Ensure the registry returns all service providers.
     */
    public function test_it_can_return_all_service_providers(): void
    {
        $registry = new ExtensionRegistry();

        $registry->register(
            new FakeExtension()
        );

        $this->assertSame(
            [],
            $registry->providers()
        );
    }
}
