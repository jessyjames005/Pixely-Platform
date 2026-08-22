<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Extensions\Discovery\ExtensionRepository;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates the OpenAPI specification from extension API definitions.
 */
final class GenerateOpenApi extends Command
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

        $specification = [
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
        ];
        $operationIds = [];

        $manifests = $this->extensionRepository->manifests(
            app_path('Extensions'),
        );

        foreach ($manifests as $manifest) {
            $apiFile = $manifest->path . '/api.yml';

            if (! is_file($apiFile)) {
                continue;
            }

            $definition = Yaml::parseFile($apiFile);

            if (! is_array($definition)) {
                continue;
            }

            if (! $this->validateDefinition(
                $definition,
                $manifest->id,
            )) {
                return self::FAILURE;
            }

            foreach ($definition['paths'] as $path => $operations) {
                if (isset($specification['paths'][$path])) {
                    $this->error(
                        "Duplicate OpenAPI path '{$path}' found in extension '{$manifest->id}'."
                    );

                    return self::FAILURE;
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
                            "Duplicate OpenAPI operationId '{$operationId}' found in extension '{$manifest->id}'."
                        );

                        return self::FAILURE;
                    }

                    $operationIds[] = $operationId;
                }

                $specification['paths'][$path] = $operations;
            }

            if (! empty($definition['tags'])) {
                $specification['tags'] = array_merge(
                    $specification['tags'],
                    $definition['tags'],
                );
            }
        }

        file_put_contents(
            base_path('docs/api/openapi.yml'),
            Yaml::dump($specification, 10, 2),
        );

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
        if (! isset($definition['paths']) || ! is_array($definition['paths'])) {
            $this->error(
                "Extension '{$extensionId}' must define a valid 'paths' section."
            );

            return false;
        }

        foreach ($definition['paths'] as $path => $operations) {
            if (! is_string($path) || ! str_starts_with($path, '/')) {
                $this->error(
                    "Extension '{$extensionId}' contains an invalid OpenAPI path."
                );

                return false;
            }

            if (! is_array($operations)) {
                $this->error(
                    "OpenAPI path '{$path}' in extension '{$extensionId}' is invalid."
                );

                return false;
            }

            foreach ($operations as $method => $operation) {
                if (! in_array(
                    strtolower((string) $method),
                    ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'],
                    true,
                )) {
                    continue;
                }

                if (! is_array($operation)) {
                    $this->error(
                        "Operation '{$method} {$path}' in extension '{$extensionId}' is invalid."
                    );

                    return false;
                }

                if (
                    ! isset($operation['operationId'])
                    || ! is_string($operation['operationId'])
                    || $operation['operationId'] === ''
                ) {
                    $this->error(
                        "Operation '{$method} {$path}' in extension '{$extensionId}' must define an operationId."
                    );

                    return false;
                }

                if (
                    ! isset($operation['responses'])
                    || ! is_array($operation['responses'])
                    || $operation['responses'] === []
                ) {
                    $this->error(
                        "Operation '{$method} {$path}' in extension '{$extensionId}' must define responses."
                    );

                    return false;
                }
            }
        }

        return true;
    }
}
