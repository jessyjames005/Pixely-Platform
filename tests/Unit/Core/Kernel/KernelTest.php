<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Kernel;

use App\Core\Kernel\Kernel;
use Tests\TestCase;

/**
 * Tests for the Pixely Kernel.
 */
final class KernelTest extends TestCase
{
    /**
     * Ensure the kernel boots correctly.
     */
    public function test_kernel_boots(): void
    {
        $kernel = app(Kernel::class);

        $kernel->boot();

        $this->assertTrue($kernel->isBooted());
    }

    /**
     * Ensure the kernel shuts down correctly.
     */
    public function test_kernel_shuts_down(): void
    {
        $kernel = app(Kernel::class);

        $kernel->boot();
        $kernel->shutdown();

        $this->assertFalse($kernel->isBooted());
    }
}
