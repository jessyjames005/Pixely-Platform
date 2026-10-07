<?php

declare(strict_types=1);

use App\Core\Websites\Contracts\WebsiteEngineInterface;
use App\Core\Websites\Contracts\WebsiteNavigationProviderInterface;
use App\Core\Websites\Persistence\Models\PageRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes only active menu items linked to published pages', function (): void {
    /** @var WebsiteEngineInterface $engine */
    $engine = app(WebsiteEngineInterface::class);

    $published = $engine->createPage([
        'title' => 'About',
        'status' => 'published',
    ]);

    $draft = $engine->createPage([
        'title' => 'Draft',
        'status' => 'draft',
    ]);

    $engine->createMenu([
        'name' => 'Main navigation',
        'code' => 'main',
        'items' => [
            ['title' => 'About', 'type' => 'page', 'pageId' => $published->id, 'active' => true],
            ['title' => 'Draft', 'type' => 'page', 'pageId' => $draft->id, 'active' => true],
            ['title' => 'Disabled', 'type' => 'external', 'targetUrl' => 'https://example.test', 'active' => false],
            ['title' => 'External', 'type' => 'external', 'targetUrl' => 'https://example.test', 'active' => true],
        ],
    ]);

    /** @var WebsiteNavigationProviderInterface $provider */
    $provider = app(WebsiteNavigationProviderInterface::class);

    expect($provider->navigation('main'))->toHaveCount(2)
        ->and($provider->navigation('main')[0]['href'])->toBe('/about')
        ->and($provider->navigation('main')[1]['href'])->toBe('https://example.test');
});

it('returns an empty list for an unknown menu', function (): void {
    /** @var WebsiteNavigationProviderInterface $provider */
    $provider = app(WebsiteNavigationProviderInterface::class);

    expect($provider->navigation('missing'))->toBe([]);
});
