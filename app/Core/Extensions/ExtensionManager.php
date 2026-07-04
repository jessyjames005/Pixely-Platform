<?php

declare(strict_types=1);

namespace App\Core\Extensions;

/**
 * Manages the lifecycle of registered extensions.
 */
final class ExtensionManager
{
    public function __construct(
        private readonly ExtensionRegistry $registry,
    ) {
    }

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
}
