<?php

declare(strict_types=1);

namespace App\Core\Extensions\Configuration;

/**
 * Persists extension configuration overrides.
 */
interface ExtensionConfigurationRepositoryInterface
{
    /**
     * Save configuration overrides for an extension.
     *
     * @param array<string, mixed> $configuration
     */
    public function save(
        string $extensionId,
        array $configuration,
    ): void;

    /**
     * Load configuration overrides for an extension.
     *
     * @return array<string, mixed>
     */
    public function load(string $extensionId): array;
}
