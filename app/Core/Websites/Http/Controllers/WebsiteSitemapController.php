<?php

declare(strict_types=1);

namespace App\Core\Websites\Http\Controllers;

use App\Core\Websites\Persistence\Models\PageRecord;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Exposes public SEO endpoints for the Website Engine.
 *
 * Provides an XML sitemap of published pages and a robots.txt file.
 */
final class WebsiteSitemapController extends Controller
{
    /**
     * Build and return the XML sitemap of published pages.
     */
    public function sitemap(): Response
    {
        $pages = PageRecord::query()
            ->where('status', 'published')
            ->orderBy('slug')
            ->get(['slug', 'updated_at']);

        $urls = '';
        foreach ($pages as $page) {
            $urls .= "  <url>\n    <loc>/" . e((string) $page->slug) . "</loc>\n  </url>\n";
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . $urls
            . "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Return the robots.txt policy for crawlers.
     */
    public function robots(): Response
    {
        return response("User-agent: *\nAllow: /\nSitemap: /sitemap.xml\n", 200, ['Content-Type' => 'text/plain']);
    }
}
