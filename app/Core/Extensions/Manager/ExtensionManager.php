<?php

declare(strict_types=1);

namespace App\Core\Extensions\Manager;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Registry\ExtensionRegistry;
use App\Core\Extensions\Discovery\ExtensionRepository;

/**
 * Manages the lifecycle of registered extensions.
 */
final class ExtensionManager
{
    public function __construct(
        private readonly ExtensionRegistry $registry,
        private readonly ExtensionRepository $repository,
    ) {}

    /**
     * Register a new extension.
     */
    public function register(ExtensionInterface $extension): void
    {
        $this->registry->register($extension);
    }

    /**
     * Boot all registered extensions.
     */
    public function boot(): void
    {
        $this->registry->boot();
    }

    /**
     * Return all registered extensions.
     *
     * @return ExtensionInterface[]
     */
    public function all(): array
    {
        return $this->registry->all();
    }

    /**
     * Load extensions from a directory.
     */
    public function load(string $path): void
    {
        foreach ($this->repository->all($path) as $extension) {
            $this->register($extension);
        }
    }
}
