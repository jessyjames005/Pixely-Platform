<?php

declare(strict_types=1);

namespace App\Core\Extensions\Manager;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\Registry\ExtensionRegistry;
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
        $this->registry->register($extension);

        $this->stateRepository->save(
            new ExtensionState(
                extension: $extension,
                status: ExtensionStatus::Enabled,
            ),
        );
    }

    /**
     * Boot all registered extensions.
     */
    public function boot(): void
    {
        $this->registry->boot();

        foreach ($this->registry->all() as $extension) {
            $this->stateRepository->update(
                new ExtensionState(
                    extension: $extension,
                    status: ExtensionStatus::Enabled,
                ),
            );
        }
    }

    /**
     * Determine whether an extension is registered.
     */
    public function has(string $id): bool
    {
        return $this->registry->has($id);
    }

    /**
     * Enable a registered extension.
     */
    public function enable(string $id): void
    {
        $extension = $this->registry->get($id);

        $this->stateRepository->update(
            new ExtensionState(
                extension: $extension,
                status: ExtensionStatus::Enabled,
            ),
        );
    }

    /**
     * Disable a registered extension.
     */
    public function disable(string $id): void
    {
        $extension = $this->registry->get($id);

        $this->stateRepository->update(
            new ExtensionState(
                extension: $extension,
                status: ExtensionStatus::Disabled,
            ),
        );
    }

    /**

     * Determine whether an extension is enabled.
     */
    public function isEnabled(string $id): bool
    {
        $state = $this->stateRepository->find($id);

        return $state?->status === ExtensionStatus::Enabled;
    }

    /**

     * Return the state of a registered extension.
     */
    public function findState(string $id): ?ExtensionState
    {
        return $this->stateRepository->find($id);
    }

    /**

     * Return all enabled extensions.
     *
     * @return array<string, ExtensionInterface>
     */
    public function enabled(): array
    {
        return array_filter(
            $this->registry->all(),
            fn(ExtensionInterface $extension): bool => $this->isEnabled(
                $extension->manifest()->id,
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
            $this->registry->all(),
            fn(ExtensionInterface $extension): bool => ! $this->isEnabled(
                $extension->manifest()->id,
            ),
        );
    }

    /**
     * Return all registered extensions.
     *
     * @return array<string, ExtensionInterface>
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
