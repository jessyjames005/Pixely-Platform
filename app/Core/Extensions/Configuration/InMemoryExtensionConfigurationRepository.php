<?php

declare(strict_types=1);

namespace App\Core\Extensions\Configuration;

/**
 * Stores extension configuration overrides in memory.
 *
 * Useful for tests and lightweight runtime usage.
 */
final class InMemoryExtensionConfigurationRepository implements ExtensionConfigurationRepositoryInterface
{
    /**
     * Stored configuration indexed by extension identifier.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $configurations = [];

    /**
     * Save configuration overrides.
     *
     * @param array<string, mixed> $configuration
     */
    public function save(
        string $extensionId,
        array $configuration,
    ): void {
        $this->configurations[$extensionId] = $configuration;
    }

    /**
     * Load configuration overrides.
     *
     * @return array<string, mixed>
     */
    public function load(string $extensionId): array
    {
        return $this->configurations[$extensionId] ?? [];
    }
}
