<?php

declare(strict_types=1);

namespace App\Extensions\ExamplePage\tests\Functional;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Functional test proving the ExamplePage extension publishes content
 * through the Website Engine public page contract.
 */
final class ExamplePagePublishTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The example page is served as a Website Engine page payload.
     */
    public function test_it_publishes_example_page_content_through_the_website_engine(): void
    {
        $response = $this->getJson('/api/v1/public/example-page');

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'example-page')
            ->assertJsonPath('data.title', 'Example Page')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.template', 'default')
            ->assertJsonPath('data.blocks.0.type', 'heading')
            ->assertJsonPath('data.blocks.1.type', 'paragraph');
    }
}
