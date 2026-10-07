<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Websites;

use App\Core\Websites\Persistence\Models\PageRecord;
use App\Core\Websites\Persistence\Models\MenuRecord;
use App\Core\Websites\Persistence\Models\MenuItemRecord;
use Database\Seeders\WebsiteDefaultContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WebsiteDefaultContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_public_pages_and_main_menu_are_seeded(): void
    {
        $this->seed(WebsiteDefaultContentSeeder::class);

        $this->assertSame(3, PageRecord::query()->whereIn('slug', ['home', 'about', 'contact'])->count());
        $this->assertSame(3, PageRecord::query()->where('status', 'published')->count());

        $menu = MenuRecord::query()->where('code', 'main')->firstOrFail();

        $this->assertSame(3, MenuItemRecord::query()->where('menu_id', $menu->id)->count());
    }

    public function test_default_content_seeder_is_idempotent(): void
    {
        $this->seed(WebsiteDefaultContentSeeder::class);
        $this->seed(WebsiteDefaultContentSeeder::class);

        $this->assertSame(3, PageRecord::query()->whereIn('slug', ['home', 'about', 'contact'])->count());
        $this->assertSame(3, MenuItemRecord::query()->count());
    }
}
