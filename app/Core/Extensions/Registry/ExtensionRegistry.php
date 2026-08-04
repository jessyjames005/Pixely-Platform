<?php

declare(strict_types=1);

namespace App\Core\Extensions\Registry;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Exceptions\ExtensionAlreadyRegisteredException;
use App\Core\Extensions\Exceptions\ExtensionNotFoundException;

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
     * @throws ExtensionAlreadyRegisteredException When the extension is already registered.
     */
    public function register(ExtensionInterface $extension): void
    {
        $id = $extension->manifest()->id;

        if ($this->has($id)) {
            throw new ExtensionAlreadyRegisteredException(
                "Extension [{$id}] is already registered."
            );
        }

        $this->extensions[$id] = $extension;
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

    /**
     * Determine whether an extension is registered.
     */
    public function has(string $id): bool
    {
        return isset($this->extensions[$id]);
    }

    /**
     * Return a registered extension.
     *
     * @throws ExtensionNotFoundException When the extension is not registered.
     */
    public function get(string $id): ExtensionInterface
    {
        if (! $this->has($id)) {
            throw new ExtensionNotFoundException(
                "Extension [{$id}] is not registered."
            );
        }

        return $this->extensions[$id];
    }

    /**
     * Return the number of registered extensions.
     */
    public function count(): int
    {
        return count($this->extensions);
    }

    /**
     * Return all registered service providers.
     *
     * @return array<class-string>
     */
    public function providers(): array
    {
        $providers = [];

        foreach ($this->extensions as $extension) {
            array_push(
                $providers,
                ...$extension->providers()
            );
        }

        return $providers;
    }
}
