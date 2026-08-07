<?php

declare(strict_types=1);

namespace App\Core\Extensions\Contracts;

use App\Core\Extensions\State\ExtensionState;

/**
 * Provides access to extension runtime states.
 */
interface ExtensionStateRepositoryInterface
{
    /**
     * Return all extension states.
     *
     * @return array<string, ExtensionState>
     */
    public function all(): array;

    /**
     * Return an extension state by its identifier.
     */
    public function find(string $id): ?ExtensionState;

    /**
     * Persist an extension state.
     */
    public function save(ExtensionState $state): void;

    public function update(ExtensionState $state): void;
}
