<?php

namespace Tests\Unit\Core\Kernel;

use App\Core\Kernel\Kernel;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function test_kernel_is_not_booted_by_default(): void
    {
        $kernel = new Kernel();

        $this->assertFalse($kernel->isBooted());
    }

    public function test_kernel_boots(): void
    {
        $kernel = new Kernel();
        $kernel->boot();

        $this->assertTrue($kernel->isBooted());
    }

    public function test_kernel_shuts_down(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $kernel->shutdown();

        $this->assertFalse($kernel->isBooted());
    }
}