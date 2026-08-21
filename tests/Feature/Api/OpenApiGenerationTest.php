<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Tests OpenAPI specification generation.
 */
final class OpenApiGenerationTest extends TestCase
{
    /**
     * Ensure the OpenAPI specification can be generated.
     */
    public function test_openapi_specification_can_be_generated(): void
    {
        $outputFile = base_path('docs/api/openapi.yml');

        $this->artisan('openapi:generate')
            ->assertSuccessful();

        $this->assertFileExists($outputFile);

        $content = file_get_contents($outputFile);

        $this->assertIsString($content);
        $this->assertStringContainsString('openapi:', $content);
        $this->assertStringContainsString('Pixely Platform API', $content);
        $this->assertStringContainsString('/api/v1/gallery:', $content);
    }
}
