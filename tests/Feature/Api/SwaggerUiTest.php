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
        // Compiled frontend assets are only present after `npm run build`
        // (absent from CI); this test only covers the route and the view.
        $this->withoutVite();

        $response = $this->get(
            route('api.documentation'),
        );

        $response->assertSuccessful();
        $response->assertViewIs('api.swagger');
    }
}
