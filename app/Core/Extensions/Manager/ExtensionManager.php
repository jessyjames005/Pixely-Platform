<?php

declare(strict_types=1);

namespace App\Core\Extensions\Manager;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\Discovery\ExtensionRepository;
use App\Core\Extensions\Enum\ExtensionStatus;
use App\Core\Extensions\Exceptions\ExtensionDependencyCycleException;
use App\Core\Extensions\Exceptions\ExtensionDependencyException;
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
    ) {
    }

    /**
     * Register a new extension.
     */
    /**
     * Register a newly discovered extension.
     *
     * Only sets its initial state to Enabled the first time it's ever
     * seen. Extensions are re-discovered from disk on every request
     * (PHP has no long-running process state), so unconditionally
     * writing Enabled here — as this used to do — silently undid any
     * disable() the moment the very next request came in: this method
     * runs before the disabled extension's own state could ever be
     * read back.
     */
    public function register(ExtensionInterface $extension): void
    {
        $this->registry->register($extension);

        $id = $extension->manifest()->id;

        if ($this->stateRepository->find($id) === null) {
            $this->stateRepository->save(
                new ExtensionState(
                    extension: $extension,
                    status: ExtensionStatus::Enabled,
                ),
            );
        }
    }

    /**
     * Boot every currently enabled extension.
     *
     * Only loops over enabled() — it used to boot every registered
     * extension unconditionally and force its status back to Enabled
     * in the same pass, which combined with the register() bug above
     * meant a disabled extension's own boot() (and, in the Kernel,
     * its service providers) kept running anyway.
     */
    public function boot(): void
    {
        foreach ($this->enabled() as $extension) {
            $extension->boot();
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
     *
     * @throws ExtensionDependencyException When a dependency is missing or disabled.
     */
    public function enable(string $id): void
    {
        $extension = $this->registry->get($id);

        $this->assertDependenciesEnabled($id);

        $this->stateRepository->update(
            new ExtensionState(
                extension: $extension,
                status: ExtensionStatus::Enabled,
            ),
        );
    }

    /**

     * Ensure that all extension dependencies are enabled.
     *
     * @param array<string, bool> $visiting
     *
     * @throws ExtensionDependencyException When a dependency is missing or disabled.
     * @throws ExtensionDependencyCycleException When a circular dependency is detected.
     */
    private function assertDependenciesEnabled(
        string $id,
        array &$visiting = [],
    ): void {
        if (isset($visiting[$id])) {
            throw new ExtensionDependencyCycleException(
                "Circular extension dependency detected: [{$id}].",
            );
        }

        $visiting[$id] = true;

        $extension = $this->registry->get($id);

        foreach ($extension->manifest()->dependencies as $dependencyId) {
            if (! $this->registry->has($dependencyId)) {
                throw new ExtensionDependencyException(
                    "Extension [{$id}] requires missing extension [{$dependencyId}].",
                );
            }

            $this->assertDependenciesEnabled(
                $dependencyId,
                $visiting,
            );

            if (! $this->isEnabled($dependencyId)) {
                throw new ExtensionDependencyException(
                    "Extension [{$id}] cannot be enabled because dependency [{$dependencyId}] is disabled.",
                );
            }
        }

        unset($visiting[$id]);
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
            fn (ExtensionInterface $extension): bool => $this->isEnabled(
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
            fn (ExtensionInterface $extension): bool => ! $this->isEnabled(
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
