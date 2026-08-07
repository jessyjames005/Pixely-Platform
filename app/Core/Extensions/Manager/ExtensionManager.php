<?php

declare(strict_types=1);

namespace App\Core\Extensions\Manager;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Registry\ExtensionRegistry;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\State\ExtensionState;

/**
 * Manages the lifecycle of registered extensions.
 */
final class ExtensionManager
{
    public function __construct(
        private readonly ExtensionRegistry $registry,
        private readonly ExtensionRepository $repository,
        private readonly ExtensionStateRepositoryInterface $stateRepository,
    ) {}

    /**
     * Register a new extension.
     */
    public function register(ExtensionInterface $extension): void
    {
        $state = new ExtensionState(
            $extension,
            ExtensionStatus::Enabled,
        );

        $this->stateRepository->save($state);

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

    /**
     * Return the state of an extension.
     */
    public function findState(string $id): ?ExtensionState
    {
        return $this->stateRepository->find($id);
    }

    /**
     * Determine whether an extension is enabled.
     */
    public function isEnabled(string $id): bool
    {
        return $this->findState($id)?->status === ExtensionStatus::Enabled;
    }

    /**
     * Return all enabled extensions.
     *
     * @return array<string, ExtensionInterface>
     */
    public function enabled(): array
    {
        return array_filter(
            $this->all(),
            fn(ExtensionInterface $extension): bool => $this->isEnabled(
                $extension->manifest()->id
            ),
        );
    }

    /**
     * Return all disabled extensions.
     *
     * @return array<string, ExtensionInterface>
     */
    public function disabled(): array
    {
        return array_filter(
            $this->all(),
            fn(ExtensionInterface $extension): bool => ! $this->isEnabled(
                $extension->manifest()->id
            ),
        );
    }

    /**
     * Enable an extension.
     */
    public function enable(string $id): void
    {
        $state = $this->stateRepository->find($id);

        if ($state === null) {
            return;
        }

        $this->stateRepository->save(
            $state->enable()
        );
    }

    /**
     * Disable an extension.
     */
    public function disable(string $id): void
    {
        $state = $this->stateRepository->find($id);

        if ($state === null) {
            return;
        }

        $this->stateRepository->save(
            $state->disable()
        );
    }

    public function has(string $id): bool
    {
        return isset(
            $this->all()[$id]
        );
    }
}
