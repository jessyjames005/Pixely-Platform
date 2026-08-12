<?php

declare(strict_types=1);

namespace App\Core\Extensions\Configuration;

use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Configuration\ExtensionConfigurationRepositoryInterface;

/**
 * Provides configuration values for an extension.
 *
 * Configuration is composed of extension defaults and runtime overrides.
 */
final class ExtensionConfiguration
{
    /**
     * Runtime configuration overrides.
     *
     * @var array<string, mixed>
     */
    private array $overrides = [];

    /**
     * Configuration persistence repository.
     */
    private readonly ExtensionConfigurationRepositoryInterface $repository;

    /**
     * Create a new extension configuration.
     */
    public function __construct(
        private readonly ExtensionInterface $extension,
        ?ExtensionConfigurationRepositoryInterface $repository = null,
    ) {
        $this->repository = $repository
            ?? new InMemoryExtensionConfigurationRepository();

        $this->overrides = $this->repository->load(
            $this->extension->manifest()->id,
        );
    }

    /**
     * Return all effective configuration values.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $defaults = $this->defaults();

        return $this->mergeRecursive(
            $defaults,
            $this->overrides,
        );
    }

    /**
     * Set a configuration override.
     *
     * Dot notation can be used for nested values.
     */
    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);

        $configuration = &$this->overrides;

        foreach ($segments as $segment) {
            if (! isset($configuration[$segment]) || ! is_array($configuration[$segment])) {
                $configuration[$segment] = [];
            }

            $configuration = &$configuration[$segment];
        }

        $configuration = $value;

        unset($configuration);
    }

    /**
     * Return a configuration value.
     *
     * Dot notation can be used for nested values.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);

        $value = $this->overrides;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                $value = $this->defaults();

                foreach ($segments as $defaultSegment) {
                    if (
                        ! is_array($value)
                        || ! array_key_exists($defaultSegment, $value)
                    ) {
                        return $default;
                    }

                    $value = $value[$defaultSegment];
                }

                return $value;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Determine whether a configuration override exists.
     *
     * Dot notation can be used for nested values.
     */
    public function has(string $key): bool
    {
        $segments = explode('.', $key);
        $value = $this->overrides;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Forget a configuration override.
     *
     * Dot notation can be used for nested values.
     */
    public function forget(string $key): void
    {
        $segments = explode('.', $key);
        $lastSegment = array_pop($segments);

        if ($lastSegment === null) {
            return;
        }

        $configuration = &$this->overrides;

        foreach ($segments as $segment) {
            if (! isset($configuration[$segment]) || ! is_array($configuration[$segment])) {
                return;
            }

            $configuration = &$configuration[$segment];
        }

        unset($configuration[$lastSegment]);

        unset($configuration);
    }

    /**
     * Return the extension default configuration.
     *
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        if ($this->extension instanceof ExtensionConfigurableInterface) {
            return $this->extension->defaultConfiguration();
        }

        return [
            'enabled' => true,
        ];
    }

    /**
     * Merge nested configuration values.
     *
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function mergeRecursive(
        array $defaults,
        array $overrides,
    ): array {
        foreach ($overrides as $key => $value) {
            if (
                is_array($value)
                && isset($defaults[$key])
                && is_array($defaults[$key])
            ) {
                $defaults[$key] = $this->mergeRecursive(
                    $defaults[$key],
                    $value,
                );

                continue;
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }

    /**
     * Persist the current configuration overrides.
     */
    public function save(): void
    {
        $this->repository->save(
            $this->extension->manifest()->id,
            $this->overrides,
        );
    }
}
