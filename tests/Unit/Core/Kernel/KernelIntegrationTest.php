<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Kernel;

use App\Core\Kernel\Kernel;
use Tests\TestCase;
use App\Extensions\Gallery\GalleryExtension;

/**
 * Integration tests for the Pixely Kernel.
 */
final class KernelIntegrationTest extends TestCase
{
    /**
     * Ensure the kernel registers discovered extensions.
     */
    public function test_it_registers_an_extension(): void
    {
        /** @var Kernel $kernel */
        $kernel = app(Kernel::class);

        $kernel->boot();

        $this->assertArrayHasKey('gallery', $kernel->extensions());

        $extension = $kernel->extensions()['gallery'];

        $this->assertInstanceOf(
            GalleryExtension::class,
            $extension
        );
    }

    /**
     * Ensure the kernel boots successfully.
     */
    public function test_it_boots_with_registered_extensions(): void
    {
        /** @var Kernel $kernel */
        $kernel = app(Kernel::class);

        $kernel->boot();

        $this->assertTrue($kernel->isBooted());
        $this->assertCount(2, $kernel->extensions());
    }
}
