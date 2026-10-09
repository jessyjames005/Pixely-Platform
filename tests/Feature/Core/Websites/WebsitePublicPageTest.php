<?php

declare(strict_types=1);

use App\Core\Websites\Persistence\Models\PageRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public page endpoint returns published pages', function (): void {
    PageRecord::query()->create([
        'id' => 'page_public',
        'slug' => 'about',
        'title' => 'About',
        'status' => 'published',
        'template' => 'default',
        'seo' => ['title' => 'About Pixely'],
        'blocks' => [['type' => 'paragraph', 'text' => 'About Pixely']],
    ]);

    $this->getJson('/api/v1/website/public/pages/about')
        ->assertOk()
        ->assertJsonPath('data.slug', 'about')
        ->assertJsonPath('data.status', 'published');
});

test('public page endpoint hides drafts', function (): void {
    PageRecord::query()->create([
        'id' => 'page_draft',
        'slug' => 'draft-page',
        'title' => 'Draft',
        'status' => 'draft',
        'template' => 'default',
        'seo' => [],
        'blocks' => [],
    ]);

    $this->getJson('/api/v1/website/public/pages/draft-page')
        ->assertNotFound();
});


test('public page endpoint filters unsupported blocks and unsafe CTA URLs', function (): void {
    PageRecord::query()->create([
        'id' => 'page_safe_blocks',
        'slug' => 'safe-blocks',
        'title' => 'Safe blocks',
        'status' => 'published',
        'template' => 'default',
        'seo' => [],
        'blocks' => [
            ['type' => 'heading', 'text' => 'Welcome'],
            ['type' => 'cta', 'text' => 'Unsafe', 'href' => 'javascript:alert(1)'],
            ['type' => 'custom-html', 'text' => '<script>alert(1)</script>'],
        ],
    ]);

    $this->getJson('/api/v1/website/public/pages/safe-blocks')
        ->assertOk()
        ->assertJsonPath('data.blocks', [
            ['type' => 'heading', 'text' => 'Welcome'],
        ]);
});
