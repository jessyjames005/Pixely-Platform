<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Websites\Persistence\MenuItemRecord;
use App\Core\Websites\Persistence\MenuRecord;
use App\Core\Websites\Persistence\PageRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the minimal public website content used by a fresh Pixely installation.
 *
 * The seeder is intentionally explicit and idempotent: production installations
 * can run it without replacing content that administrators already changed.
 */
final class WebsiteDefaultContentSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'home' => [
                'title' => 'Welcome to Pixely Platform',
                'seo' => [
                    'title' => 'Pixely Platform',
                    'description' => 'A modular platform for your website, extensions and digital experiences.',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Build your digital experience.'],
                    ['type' => 'paragraph', 'text' => 'Pixely Platform brings your public website, user space and administration together in one extensible platform.'],
                    ['type' => 'cta', 'text' => 'Discover Pixely', 'href' => '/about'],
                ],
            ],
            'about' => [
                'title' => 'About Pixely',
                'seo' => [
                    'title' => 'About Pixely Platform',
                    'description' => 'Learn about the Pixely Platform vision and its multi-surface architecture.',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'One platform, multiple experiences.'],
                    ['type' => 'paragraph', 'text' => 'Pixely separates platform capabilities from extension experiences, allowing the website, user space and administration to evolve together.'],
                    ['type' => 'cta', 'text' => 'Contact us', 'href' => '/contact'],
                ],
            ],
            'contact' => [
                'title' => 'Contact',
                'seo' => [
                    'title' => 'Contact — Pixely Platform',
                    'description' => 'Get in touch with the Pixely Platform team.',
                ],
                'blocks' => [
                    ['type' => 'heading', 'text' => 'Let’s talk.'],
                    ['type' => 'paragraph', 'text' => 'This page is ready to be connected to the future Contact extension and form workflow.'],
                ],
            ],
        ];

        foreach ($pages as $slug => $content) {
            PageRecord::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'id' => 'website-page-' . $slug,
                    'title' => $content['title'],
                    'status' => 'published',
                    'template' => 'default',
                    'seo' => $content['seo'],
                    'blocks' => $content['blocks'],
                ],
            );
        }

        $menu = MenuRecord::query()->updateOrCreate(
            ['code' => 'main'],
            ['id' => 'website-menu-main', 'name' => 'Main navigation'],
        );

        foreach (['home', 'about', 'contact'] as $order => $slug) {
            $page = PageRecord::query()->where('slug', $slug)->firstOrFail();

            MenuItemRecord::query()->updateOrCreate(
                ['menu_id' => $menu->id, 'page_id' => $page->id],
                [
                    'id' => 'website-menu-main-' . $slug,
                    'type' => 'page',
                    'title' => $page->title === 'Welcome to Pixely Platform' ? 'Home' : Str::title($slug),
                    'target_url' => null,
                    'extension_id' => null,
                    'slug' => $slug,
                    'sort_order' => $order,
                    'active' => true,
                ],
            );
        }
    }
}
