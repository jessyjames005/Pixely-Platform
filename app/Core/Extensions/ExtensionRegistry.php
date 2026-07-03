<?php

declare(strict_types=1);

namespace App\Core\Extensions;

use InvalidArgumentException;

/**
 * Responsible for registering and managing extensions.
 */
final class ExtensionRegistry
{
    /**
     * @var ExtensionInterface[]
     */
    private array $extensions = [];

    /**
     * Register a new extension.
     */
    public function register(ExtensionInterface $extension): void
    {
        $name = $extension->getName();

        if (isset($this->extensions[$name])) {
            throw new InvalidArgumentException("Extension [{$name}] already registered.");
        }

        $this->extensions[$name] = $extension;

        $extension->register();
    }

    /**
     * Boot all registered extensions.
     */
    public function boot(): void
    {
        foreach ($this->extensions as $extension) {
            $extension->boot();
        }
    }

    /**
     * Get all registered extensions.
     *
     * @return ExtensionInterface[]
     */
    public function all(): array
    {
        return $this->extensions;
    }
}