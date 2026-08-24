<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Tests the Swagger UI documentation page.
 */
final class SwaggerUiTest extends TestCase
{
    /**
     * Ensure the Swagger UI documentation page is accessible.
     */
    public function test_swagger_ui_documentation_page_is_accessible(): void
    {
        $response = $this->get(
            route('api.documentation'),
        );

        $response->assertSuccessful();
        $response->assertViewIs('api.swagger');
    }
}
