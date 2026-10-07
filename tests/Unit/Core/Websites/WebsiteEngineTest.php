<?php

declare(strict_types=1);

use App\Core\Websites\Models\Menu;
use App\Core\Websites\Models\PageModel;
use App\Core\Websites\Services\WebsiteEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists pages and applies filters', function (): void {
    $engine = app(WebsiteEngine::class);

    $engine->createPage(['title' => 'Home', 'status' => 'published']);
    $engine->createPage(['title' => 'Draft', 'status' => 'draft']);

    $pages = $engine->listPages(['status' => 'published']);

    expect($pages)->toHaveCount(1)
        ->and($pages[0])->toBeInstanceOf(PageModel::class)
        ->and($pages[0]->title)->toBe('Home');
});

it('persists menus with ordered items', function (): void {
    $engine = app(WebsiteEngine::class);

    $menu = $engine->createMenu([
        'name' => 'Main navigation',
        'code' => 'main',
        'items' => [
            ['type' => 'external', 'title' => 'Contact', 'targetUrl' => '/contact', 'sortOrder' => 1],
            ['type' => 'external', 'title' => 'Home', 'targetUrl' => '/', 'sortOrder' => 0],
        ],
    ]);

    expect($menu)->toBeInstanceOf(Menu::class)
        ->and($menu->items)->toHaveCount(2)
        ->and($menu->items[0]->title)->toBe('Home')
        ->and($engine->getMenu('main')?->items[1]->title)->toBe('Contact');
});
