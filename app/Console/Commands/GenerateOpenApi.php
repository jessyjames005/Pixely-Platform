<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Discovery\ExtensionRepository;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates the OpenAPI specification from extension API definitions.
 */
class GenerateOpenApi extends Command
{
    /**
     * Artisan command signature.
     */
    protected $signature = 'openapi:generate';

    /**
     * Artisan command description.
     */
    protected $description = 'Generate the OpenAPI specification from extension API definitions.';

    public function __construct(
        private readonly ExtensionRepository $extensionRepository,
    ) {
        parent::__construct();
    }

    /**
     * Generate the OpenAPI specification.
     */
    public function handle(): int
    {
        $this->info('Generating OpenAPI specification...');

        $specification = $this->createBaseSpecification();
        $operationIds = [];

        if (! $this->loadCoreComponents($specification)) {
            return self::FAILURE;
        }

        $manifests = $this->extensionRepository->manifests(
            $this->extensionsPath(),
        );

        foreach ($manifests as $manifest) {
            $definition = $this->loadExtensionDefinition($manifest);

            if ($definition === null) {
                continue;
            }

            if (! $this->validateDefinition(
                $definition,
                $manifest->id,
            )) {
                return self::FAILURE;
            }

            if (! $this->mergeExtensionDefinition(
                $specification,
                $definition,
                $manifest->id,
                $operationIds,
            )) {
                return self::FAILURE;
            }
        }

        $this->writeSpecification($specification);

        $this->info('OpenAPI specification generated successfully.');
        $this->line(base_path('docs/api/openapi.yml'));

        return self::SUCCESS;
    }

    /**
     * Validate an extension OpenAPI definition.
     *
     * @param array<string, mixed> $definition
     */
    private function validateDefinition(
        array $definition,
        string $extensionId,
    ): bool {
        if (! $this->validatePaths($definition, $extensionId)) {
            return false;
        }

        return $this->validateSchemas(
            $definition,
            $extensionId,
        );
    }

    /**
     * Create the base OpenAPI specification.
     *
     * @return array<string, mixed>
     */
    private function createBaseSpecification(): array
    {
        return [
            'openapi' => '3.0.3',

            'info' => [
                'title' => 'Pixely Platform API',
                'description' => 'Public API for Pixely Platform extensions.',
                'version' => '0.1.0',
            ],

            'servers' => [
                ['url' => '/'],
            ],

            'paths' => [],
            'tags' => [],

            'components' => [
                'schemas' => [],
            ],
        ];
    }

    /**
     * Load OpenAPI components defined by the platform core.
     *
     * @param array<string, mixed> $specification
     */
    private function loadCoreComponents(array &$specification): bool
    {
        $componentsFile = app_path(
            'Core/Api/OpenApi/components.yml',
        );

        if (! is_file($componentsFile)) {
            return true;
        }

        $definition = Yaml::parseFile($componentsFile);

        if (! is_array($definition)) {
            $this->error(
                'Core OpenAPI components definition is invalid.',
            );

            return false;
        }

        $schemas = $definition['components']['schemas'] ?? [];

        if (! is_array($schemas)) {
            $this->error(
                'Core OpenAPI schemas definition is invalid.',
            );

            return false;
        }

        $specification['components']['schemas'] = $schemas;

        return true;
    }

    /**
     * Load an extension OpenAPI definition.
     *
     * @return array<string, mixed>|null
     */
    private function loadExtensionDefinition(
        object $manifest,
    ): ?array {
        $apiFile = $manifest->path . '/api.yml';

        if (! is_file($apiFile)) {
            return null;
        }

        $definition = Yaml::parseFile($apiFile);

        return is_array($definition)
            ? $definition
            : null;
    }

    /**
     * Merge an extension OpenAPI definition into the specification.
     *
     * @param array<string, mixed> $specification
     * @param array<string, mixed> $definition
     * @param array<int, string> $operationIds
     */
    private function mergeExtensionDefinition(
        array &$specification,
        array $definition,
        string $extensionId,
        array &$operationIds,
    ): bool {
        if (! $this->mergeSchemas(
            $specification,
            $definition,
            $extensionId,
        )) {
            return false;
        }

        if (! $this->mergePaths(
            $specification,
            $definition,
            $extensionId,
            $operationIds,
        )) {
            return false;
        }

        $this->mergeTags(
            $specification,
            $definition,
        );

        return true;
    }

    /**
     * Merge extension schemas.
     *
     * @param array<string, mixed> $specification
     * @param array<string, mixed> $definition
     */
    private function mergeSchemas(
        array &$specification,
        array $definition,
        string $extensionId,
    ): bool {
        $schemas = $definition['components']['schemas'] ?? [];

        if (! is_array($schemas)) {
            $this->error(
                "Extension '{$extensionId}' contains invalid OpenAPI schemas.",
            );

            return false;
        }

        foreach ($schemas as $name => $schema) {
            if (isset($specification['components']['schemas'][$name])) {
                $this->error(
                    "Duplicate OpenAPI schema '{$name}' found in extension '{$extensionId}'.",
                );

                return false;
            }

            $specification['components']['schemas'][$name] = $schema;
        }

        return true;
    }

    /**
     * Merge extension paths.
     *
     * @param array<string, mixed> $specification
     * @param array<string, mixed> $definition
     * @param array<int, string> $operationIds
     */
    private function mergePaths(
        array &$specification,
        array $definition,
        string $extensionId,
        array &$operationIds,
    ): bool {
        foreach ($definition['paths'] as $path => $operations) {
            if (isset($specification['paths'][$path])) {
                $this->error(
                    "Duplicate OpenAPI path '{$path}' found in extension '{$extensionId}'.",
                );

                return false;
            }

            foreach ($operations as $operation) {
                if (! is_array($operation)) {
                    continue;
                }

                if (! isset($operation['operationId'])) {
                    continue;
                }

                $operationId = $operation['operationId'];

                if (in_array($operationId, $operationIds, true)) {
                    $this->error(
                        "Duplicate OpenAPI operationId '{$operationId}' found in extension '{$extensionId}'.",
                    );

                    return false;
                }

                $operationIds[] = $operationId;
            }

            $specification['paths'][$path] = $operations;
        }

        return true;
    }

    /**
     * Merge extension tags.
     *
     * @param array<string, mixed> $specification
     * @param array<string, mixed> $definition
     */
    private function mergeTags(
        array &$specification,
        array $definition,
    ): void {
        if (empty($definition['tags'])) {
            return;
        }

        $specification['tags'] = array_merge(
            $specification['tags'],
            $definition['tags'],
        );
    }

    /**
     * Write the generated OpenAPI specification to disk.
     *
     * @param array<string, mixed> $specification
     */
    private function writeSpecification(array $specification): void
    {
        file_put_contents(
            base_path('docs/api/openapi.yml'),
            Yaml::dump($specification, 10, 2),
        );
    }

    /**
     * Validate extension OpenAPI paths.
     *
     * @param array<string, mixed> $definition
     */
    private function validatePaths(
        array $definition,
        string $extensionId,
    ): bool {
        if (
            ! isset($definition['paths'])
            || ! is_array($definition['paths'])
        ) {
            $this->error(
                "Extension '{$extensionId}' must define a valid 'paths' section.",
            );

            return false;
        }

        foreach ($definition['paths'] as $path => $operations) {
            if (
                ! is_string($path)
                || ! str_starts_with($path, '/')
            ) {
                $this->error(
                    "Extension '{$extensionId}' contains an invalid OpenAPI path.",
                );

                return false;
            }

            if (! is_array($operations)) {
                $this->error(
                    "OpenAPI path '{$path}' in extension '{$extensionId}' is invalid.",
                );

                return false;
            }

            foreach ($operations as $method => $operation) {
                if (! $this->isHttpMethod($method)) {
                    continue;
                }

                if (! $this->validateOperation(
                    $operation,
                    $method,
                    $path,
                    $extensionId,
                )) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate an OpenAPI operation.
     *
     * @param mixed $operation
     */
    private function validateOperation(
        mixed $operation,
        string $method,
        string $path,
        string $extensionId,
    ): bool {
        if (! is_array($operation)) {
            $this->error(
                "Operation '{$method} {$path}' in extension '{$extensionId}' is invalid.",
            );

            return false;
        }

        if (
            ! isset($operation['operationId'])
            || ! is_string($operation['operationId'])
            || $operation['operationId'] === ''
        ) {
            $this->error(
                "Operation '{$method} {$path}' in extension '{$extensionId}' must define an operationId.",
            );

            return false;
        }

        if (
            ! isset($operation['responses'])
            || ! is_array($operation['responses'])
            || $operation['responses'] === []
        ) {
            $this->error(
                "Operation '{$method} {$path}' in extension '{$extensionId}' must define responses.",
            );

            return false;
        }

        return true;
    }

    /**
     * Determine whether a value is a supported HTTP method.
     */
    private function isHttpMethod(mixed $method): bool
    {
        return in_array(
            strtolower((string) $method),
            [
                'get',
                'post',
                'put',
                'patch',
                'delete',
                'options',
                'head',
                'trace',
            ],
            true,
        );
    }

    /**
     * Validate extension OpenAPI schemas.
     *
     * @param array<string, mixed> $definition
     */
    private function validateSchemas(
        array $definition,
        string $extensionId,
    ): bool {
        $schemas = $definition['components']['schemas'] ?? [];

        if (! is_array($schemas)) {
            $this->error(
                "Extension '{$extensionId}' contains invalid OpenAPI schemas.",
            );

            return false;
        }

        foreach ($schemas as $name => $schema) {
            if (
                ! is_string($name)
                || $name === ''
                || ! is_array($schema)
            ) {
                $this->error(
                    "Extension '{$extensionId}' contains an invalid OpenAPI schema.",
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Return the directory containing platform extensions.
     */
    protected function extensionsPath(): string
    {
        return app_path('Extensions');
    }
}
