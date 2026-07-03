<?php

declare(strict_types=1);

namespace App\Core\Kernel;

use App\Core\Contracts\KernelInterface;

/**
 * Default implementation of the Pixely Kernel.
 *
 * The Kernel is responsible for bootstrapping and shutting down
 * the Pixely Platform.
 */
final class Kernel implements KernelInterface
{
    /**
     * Indicates whether the platform has been booted.
     */
    private bool $booted = false;

    /**
     * Boot the Pixely Platform.
     */
    public function boot(): void
    {
        $this->booted = true;
    }

    /**
     * Shutdown the Pixely Platform.
     */
    public function shutdown(): void
    {
        $this->booted = false;
    }

    /**
     * Determine whether the platform is booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }
}