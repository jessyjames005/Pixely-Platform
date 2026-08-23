<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests OpenAPI specification generation.
 */
final class OpenApiGenerationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure the OpenAPI specification can be generated.
     */
    public function test_it_generates_the_openapi_specification(): void
    {
        $this->artisan('openapi:generate')
            ->assertExitCode(0);

        $outputFile = base_path('docs/api/openapi.yml');

        $this->assertFileExists($outputFile);

        $content = file_get_contents($outputFile);

        $this->assertIsString($content);
        $this->assertStringContainsString(
            'openapi:',
            $content,
        );
        $this->assertStringContainsString(
            '/api/v1/gallery:',
            $content,
        );
        $this->assertStringContainsString(
            "'/api/v1/gallery/{photo}':",
            $content,
        );
    }

    /**
     * Ensure all Gallery API routes are documented.
     */
    public function test_all_gallery_api_routes_are_documented(): void
    {
        $this->artisan('openapi:generate')
            ->assertExitCode(0);

        $content = file_get_contents(
            base_path('docs/api/openapi.yml'),
        );

        $this->assertIsString($content);

        $routes = [
            '/api/v1/gallery:',
            '/api/v1/gallery/upload:',
            "'/api/v1/gallery/{photo}':",
        ];

        foreach ($routes as $route) {
            $this->assertStringContainsString(
                $route,
                $content,
            );
        }

        $this->assertStringContainsString(
            'operationId: listGalleryPhotos',
            $content,
        );

        $this->assertStringContainsString(
            'operationId: uploadGalleryPhoto',
            $content,
        );

        $this->assertStringContainsString(
            'operationId: getGalleryPhoto',
            $content,
        );

        $this->assertStringContainsString(
            'operationId: updateGalleryPhoto',
            $content,
        );

        $this->assertStringContainsString(
            'operationId: deleteGalleryPhoto',
            $content,
        );
    }

    public function test_core_and_gallery_schemas_are_documented(): void
    {
        $this->artisan('openapi:generate')
            ->assertExitCode(0);

        $content = file_get_contents(
            base_path('docs/api/openapi.yml'),
        );

        $this->assertIsString($content);

        $schemas = [
            'ApiError:',
            'PaginationMeta:',
            'Photo:',
            'PhotoResponse:',
            'PhotoListResponse:',
        ];

        foreach ($schemas as $schema) {
            $this->assertStringContainsString(
                $schema,
                $content,
            );
        }
    }

    public function test_gallery_pagination_constraints_are_documented(): void
    {
        $this->artisan('openapi:generate')
            ->assertExitCode(0);

        $content = file_get_contents(
            base_path('docs/api/openapi.yml'),
        );

        $this->assertIsString($content);

        $this->assertStringContainsString(
            'minimum: 1',
            $content,
        );

        $this->assertStringContainsString(
            'maximum: 100',
            $content,
        );

        $this->assertStringContainsString(
            'default: 20',
            $content,
        );
    }

    /**
     * Ensure the generated OpenAPI specification is structurally valid.
     */
    public function test_it_generates_a_valid_openapi_specification(): void
    {
        $this->artisan('openapi:generate')
            ->assertExitCode(0);

        $this->artisan('openapi:validate')
            ->assertExitCode(0);
    }
}
