<?php

declare(strict_types=1);

namespace App\Core\Kernel;

use App\Core\Contracts\KernelInterface;
use App\Core\Extensions\ExtensionManager;

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

    public function __construct(
    private readonly ExtensionManager $extensionManager
    ) {
    }

    /**
     * Boot the Pixely Platform.
     */
    public function boot(): void
    {
        $this->booted = true;

        $this->extensionManager->boot();
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
        $this->extensionManager->register($extension);
    }

    public function extensions(): array
    {
        return $this->extensionManager->all();
    }
}
