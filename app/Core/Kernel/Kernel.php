<?php

declare(strict_types=1);

namespace App\Core\Kernel;

use App\Core\Contracts\KernelInterface;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Manager\ExtensionManager;

/**
 * Default implementation of the Pixely Kernel.
 *
 * The Kernel is responsible for bootstrapping the platform
 * and loading all extensions automatically.
 */
final class Kernel implements KernelInterface
{
    /**
     * Indicates whether the platform has been booted.
     */
    private bool $booted = false;

    /**
     * Create a new Kernel instance.
     */
    public function __construct(
        private readonly ExtensionManager $extensionManager,
        private readonly ExtensionRepository $repository,
        private readonly string $extensionsPath,
    ) {}

    /**
     * Boot the Pixely Platform.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $extensions = $this->repository->all(
            $this->extensionsPath
        );

        foreach ($extensions as $extension) {

            $this->extensionManager->register($extension);

            foreach ($extension->providers() as $provider) {
                app()->register($provider);
            }
        }

        $this->extensionManager->boot();

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

    /**
     * Get all registered extensions.
     *
     * @return array<string, \App\Core\Extensions\Contracts\ExtensionInterface>
     */
    public function extensions(): array
    {
        return $this->extensionManager->all();
    }
}
