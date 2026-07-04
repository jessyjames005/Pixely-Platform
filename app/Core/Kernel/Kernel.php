<?php

declare(strict_types=1);

namespace App\Core\Kernel;

use App\Core\Contracts\KernelInterface;
use App\Core\Extensions\ExtensionRegistry;

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
    private ExtensionRegistry $registry;

    public function __construct()
    {
        $this->registry = new ExtensionRegistry();
    }

    /**
     * Boot the Pixely Platform.
     */
    public function boot(): void
    {
        $this->booted = true;

        $this->registry->boot();
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

    public function registerExtension(\App\Core\Extensions\ExtensionInterface $extension): void
    {
        $this->registry->register($extension);
}

    public function extensions(): array
    {
        return $this->registry->all();
    }
}