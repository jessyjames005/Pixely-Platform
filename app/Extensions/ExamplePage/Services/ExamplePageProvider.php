<?php

declare(strict_types=1);

namespace App\Extensions\ExamplePage\Services;

use App\Core\Websites\Models\PageModel;

/**
 * Publishes example content through the Website Engine page contract.
 *
 * Returning a PageModel (the Website Engine's immutable DTO) is how an
 * extension publishes content without leaking its own models into Core.
 */
final class ExamplePageProvider
{
    /**
     * Build the published example page.
     *
     * @param string $slug Page slug.
     * @param string $locale Content locale.
     * @return PageModel|null The published page DTO.
     */
    public function getPage(string $slug = 'example-page', string $locale = 'en'): ?PageModel
    {
        return new PageModel(
            id: 'example-page',
            slug: $slug,
            locale: $locale,
            title: 'Example Page',
            status: 'published',
            template: 'default',
            publishedAt: now()->toIso8601String(),
            seo: [
                'title' => 'Example Page',
                'description' => 'A minimal example extension publishing content through the Website Engine.',
            ],
            blocks: [
                ['type' => 'heading', 'content' => 'Example Page'],
                ['type' => 'paragraph', 'content' => 'This content is published by the ExamplePage extension through the Website Engine PageModel contract.'],
            ],
        );
    }
}