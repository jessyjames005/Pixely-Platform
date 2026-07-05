<?php

namespace Tests\Unit\Core\Kernel;

use App\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function test_kernel_is_not_booted_by_default(): void
    {
        $kernel = app(Kernel::class);

        $this->assertFalse($kernel->isBooted());
    }

    public function test_kernel_boots(): void
    {
        $kernel = app(Kernel::class);
        $kernel->boot();

        $this->assertTrue($kernel->isBooted());
    }

    public function test_kernel_shuts_down(): void
    {
        $kernel = app(Kernel::class);
        $kernel->boot();
        $kernel->shutdown();

        $this->assertFalse($kernel->isBooted());
    }
}
