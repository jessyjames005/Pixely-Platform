<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Kernel;

use App\Core\Kernel\Kernel;
use Tests\TestCase;

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

        $extensions = $kernel->extensions();

        $this->assertArrayHasKey('gallery', $extensions);
        $this->assertCount(1, $extensions);
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
        $this->assertCount(1, $kernel->extensions());
    }
}
