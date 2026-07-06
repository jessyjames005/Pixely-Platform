<?php

declare(strict_types=1);

namespace App\Core\Extensions\Registry;

use App\Core\Extensions\Contracts\ExtensionInterface;
use InvalidArgumentException;

/**
 * Stores and manages registered extensions.
 */
final class ExtensionRegistry
{
    /**
     * Registered extensions indexed by their unique name.
     *
     * @var array<string, ExtensionInterface>
     */
    private array $extensions = [];

    /**
     * Register an extension.
     *
     * @throws InvalidArgumentException When the extension is already registered.
     */
    public function register(ExtensionInterface $extension): void
    {
        $name = $extension->manifest()->name;

        if (isset($this->extensions[$name])) {
            throw new InvalidArgumentException(
                "Extension [{$name}] is already registered."
            );
        }

        $this->extensions[$name] = $extension;
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
     * Return all registered extensions.
     *
     * @return array<string, ExtensionInterface>
     */
    public function all(): array
    {
        return $this->extensions;
    }
}
