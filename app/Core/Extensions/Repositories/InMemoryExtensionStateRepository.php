<?php

declare(strict_types=1);

namespace App\Core\Extensions\Repositories;

use App\Core\Extensions\Contracts\ExtensionStateRepositoryInterface;
use App\Core\Extensions\State\ExtensionState;

/**
 * Stores extension states in memory.
 */
final class InMemoryExtensionStateRepository implements ExtensionStateRepositoryInterface
{
    /**
     * Stored extension states.
     *
     * @var array<string, ExtensionState>
     */
    private array $states = [];

    /**
     * Return all extension states.
     *
     * @return array<string, ExtensionState>
     */
    public function all(): array
    {
        return $this->states;
    }

    /**
     * Return an extension state by its identifier.
     */
    public function find(string $id): ?ExtensionState
    {
        return $this->states[$id] ?? null;
    }

    /**
     * Persist an extension state.
     */
    public function save(ExtensionState $state): void
    {
        $this->states[$state->extension->manifest()->id] = $state;
    }

    public function update(ExtensionState $state): void
    {
        $this->states[$state->extension->manifest()->id] = $state;
    }
}
