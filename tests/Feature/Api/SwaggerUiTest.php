<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Tests the Swagger UI documentation page and the strict JSON:API contract
 * documented in the generated OpenAPI specification.
 */
final class SwaggerUiTest extends TestCase
{
    /**
     * Ensure the Swagger UI documentation page is accessible.
     */
    public function test_swagger_ui_documentation_page_is_accessible(): void
    {
        // Compiled frontend assets are only present after `npm run build`
        // (absent from CI); this test only covers the route and the view.
        $this->withoutVite();

        $response = $this->get(
            route('api.documentation'),
        );

        $response->assertSuccessful();
        $response->assertViewIs('api.swagger');
    }

    /**
     * The OpenAPI specification is a committed, generated artifact consumed by
     * the Swagger UI. It must remain valid YAML.
     */
    public function test_openapi_specification_file_is_valid_yaml(): void
    {
        $spec = $this->specification();

        $this->assertIsArray($spec);
        $this->assertNotEmpty($spec['paths'] ?? null);
        $this->assertSame('3.1.0', $spec['openapi'] ?? null);
    }

    /**
     * The entire Pixely API is strict JSON:API: `application/vnd.api+json` is
     * the only content media type — `application/json` must never leak.
     */
    public function test_specification_uses_strict_json_api_media_type_only(): void
    {
        $spec = $this->specification();

        [$jsonMediaTypes, $jsonApiMediaTypes] = $this->walkContentMediaTypes($spec);

        $this->assertEmpty(
            $jsonMediaTypes,
            'The OpenAPI document must not declare `application/json` content; use `application/vnd.api+json`.',
        );
        $this->assertNotEmpty(
            $jsonApiMediaTypes,
            'The OpenAPI document must declare at least one `application/vnd.api+json` content entry.',
        );
    }

    /**
     * The Tuleap extension must be represented as its own tag with at least
     * one operation in the generated specification.
     */
    public function test_specification_tags_the_tuleap_extension(): void
    {
        $spec = $this->specification();

        $tagNames = array_column($spec['tags'] ?? [], 'name');
        $this->assertContains('Tuleap', $tagNames);

        $tuleapPaths = 0;
        foreach ($this->iterOperations($spec) as $operation) {
            if (in_array('Tuleap', $operation['tags'] ?? [], true)) {
                $tuleapPaths++;
            }
        }

        $this->assertGreaterThan(
            0,
            $tuleapPaths,
            'At least one operation must be tagged `Tuleap`.',
        );
    }

    /**
     * Every documented operation must carry a useful, explicit summary — never a
     * generic Laravel JSON:API placeholder.
     */
    public function test_specification_has_no_generic_operation_summaries(): void
    {
        $spec = $this->specification();

        $generics = [
            'Fetch zero to many JSON API resources',
            'Fetch zero to one JSON API resource by id',
            'Create a new resource',
            'Update an existing resource',
            'Destroy a resource',
            'Fetch the resource identifier(s) for a JSON API relationship',
            'Update a resource relationship',
            'List resources',
            'Get a resource',
            'Create a resource',
            'Update a resource',
            'Delete a resource',
        ];

        $summaries = $this->collectOperationSummaries($spec);
        $offenders = array_filter($generics, static fn (string $g): bool => in_array($g, $summaries, true));

        $this->assertSame(
            [],
            $offenders,
            'The OpenAPI document contains generic operation summaries: '.implode(', ', $offenders),
        );
    }

    /**
     * Responses must use JSON:API envelopes: a successful response carries a
     * `data` key and an error response carries an `errors` key.
     */
    public function test_specification_uses_json_api_response_envelopes(): void
    {
        $spec = $this->specification();

        $foundData = false;
        $foundErrors = false;

        foreach ($this->iterOperations($spec) as $operation) {
            foreach ($operation['responses'] ?? [] as $response) {
                $this->resolveAndProbe($spec, $response, $foundData, $foundErrors);
            }

            if (isset($operation['requestBody']) && is_array($operation['requestBody'])) {
                $this->probeEnvelope($operation['requestBody'], $foundData, $foundErrors);
            }
        }

        // JSON:API error envelopes live in reusable component responses
        // (referenced from operations via `$ref`).
        foreach (($spec['components']['responses'] ?? []) as $response) {
            $this->probeEnvelope($response, $foundData, $foundErrors);
        }

        // Successful `data` envelopes may also be defined as component schemas.
        foreach (($spec['components']['schemas'] ?? []) as $schema) {
            $this->probeSchema($schema, $foundData, $foundErrors);
        }

        $this->assertTrue($foundData, 'No successful response exposes a `data` envelope.');
        $this->assertTrue($foundErrors, 'No error response exposes an `errors` envelope.');
    }

    /**
     * @return array<string, mixed>
     */
    private function specification(): array
    {
        $path = base_path('docs/api/openapi.yml');

        $this->assertFileExists($path, 'The OpenAPI specification file is missing at docs/api/openapi.yml.');

        return Yaml::parseFile($path);
    }

    /**
     * @return list<string>
     */
    private function collectOperationSummaries(array $spec): array
    {
        $summaries = [];
        foreach ($this->iterOperations($spec) as $operation) {
            if (isset($operation['summary']) && is_string($operation['summary'])) {
                $summaries[] = $operation['summary'];
            }
        }

        return array_values(array_unique($summaries));
    }

    /**
     * Inspects a response/request-body node for JSON:API envelope keys.
     */
    private function probeEnvelope(array $node, bool &$foundData, bool &$foundErrors): void
    {
        foreach (($node['content'] ?? []) as $mediaType => $media) {
            if ($mediaType !== 'application/vnd.api+json') {
                continue;
            }

            $this->probeSchema($media['schema'] ?? null, $foundData, $foundErrors);
        }
    }

    /**
     * Resolves a `$ref` response (against `#/components/...`) before probing it,
     * or probes the node directly when it carries inline content.
     */
    private function resolveAndProbe(array $spec, array $response, bool &$foundData, bool &$foundErrors): void
    {
        if (isset($response['$ref'])) {
            $response = $this->resolveRef($spec, $response['$ref']);
        }

        $this->probeEnvelope($response, $foundData, $foundErrors);
    }

    private function resolveRef(array $spec, string $ref): array
    {
        if (! str_starts_with($ref, '#/')) {
            return [];
        }

        $node = $spec;
        foreach (explode('/', substr($ref, 2)) as $segment) {
            $node = $node[$segment] ?? [];
        }

        return is_array($node) ? $node : [];
    }

    private function probeSchema(mixed $schema, bool &$foundData, bool &$foundErrors): void
    {
        if (! is_array($schema)) {
            return;
        }

        while (isset($schema['oneOf']) || isset($schema['anyOf'])) {
            $schema = $schema['oneOf'][0] ?? $schema['anyOf'][0] ?? null;
        }

        $properties = $schema['properties'] ?? null;
        if (! is_array($properties)) {
            return;
        }

        $foundData = $foundData || array_key_exists('data', $properties);
        $foundErrors = $foundErrors || array_key_exists('errors', $properties);
    }

    /**
     * Yields every documented operation (HTTP verb object) across all paths.
     *
     * @return \Generator<array>
     */
    private function iterOperations(array $spec): \Generator
    {
        $verbs = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'];

        foreach ($spec['paths'] ?? [] as $pathItem) {
            foreach ($pathItem as $verb => $operation) {
                if (! in_array($verb, $verbs, true) || ! is_array($operation)) {
                    continue;
                }

                yield $operation;
            }
        }
    }

    /**
     * @return array{0: list<string>, 1: list<string>} [application/json media types, application/vnd.api+json media types]
     */
    private function walkContentMediaTypes(array $spec): array
    {
        $json = [];
        $jsonApi = [];

        $walk = static function (mixed $node) use (&$walk, &$json, &$jsonApi): void {
            if (! is_array($node)) {
                return;
            }

            foreach ($node as $key => $value) {
                if ($key === 'content' && is_array($value)) {
                    foreach (array_keys($value) as $mediaType) {
                        if ($mediaType === 'application/json') {
                            $json[] = $mediaType;
                        } elseif ($mediaType === 'application/vnd.api+json') {
                            $jsonApi[] = $mediaType;
                        }
                    }
                }

                $walk($value);
            }
        };

        $walk($spec);

        return [$json, $jsonApi];
    }
}
