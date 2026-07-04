<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Kernel;

use App\Core\Kernel\Kernel;
use App\Extensions\Gallery\GalleryExtension;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Pixely Kernel.
 */
final class KernelIntegrationTest extends TestCase
{
    /**
     * Ensure the kernel can register an extension.
     */
    public function test_it_registers_an_extension(): void
    {
        $kernel = new Kernel();

        $kernel->registerExtension(new GalleryExtension());

        $this->assertCount(1, $kernel->extensions());
    }

    /**
     * Ensure the kernel boots after registering an extension.
     */
    public function test_it_boots_with_registered_extensions(): void
    {
        $kernel = new Kernel();

        $kernel->registerExtension(new GalleryExtension());

        $kernel->boot();

        $this->assertTrue($kernel->isBooted());
        $this->assertCount(1, $kernel->extensions());
    }
}
