<?php

declare(strict_types=1);

namespace App\Core\Websites\Services;

use App\Core\Websites\Contracts\WebsiteNavigationProviderInterface;
use App\Core\Websites\Models\MenuItem;
use App\Core\Websites\Persistence\Models\PageRecord;
use Illuminate\Support\Facades\Cache;

final class WebsiteNavigationProvider implements WebsiteNavigationProviderInterface
{
    private const NAV_TTL = 300;

    public function __construct(private WebsiteEngine $websiteEngine)
    {
    }

    public function navigation(string $code): array
    {
        return Cache::remember('website:nav:' . $code, self::NAV_TTL, function () use ($code): array {
            $menu = $this->websiteEngine->getMenu($code);

            if ($menu === null) {
                return [];
            }

            return array_values(array_filter(array_map(
                fn (MenuItem $item): ?array => $this->resolveItem($item),
                $menu->items,
            )));
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveItem(MenuItem $item): ?array
    {
        if (! $item->active) {
            return null;
        }

        $href = match ($item->type) {
            'external' => $item->targetUrl,
            'page' => $this->resolvePageUrl($item),
            'extension' => $item->targetUrl,
            default => null,
        };

        if (! is_string($href) || $href === '') {
            return null;
        }

        return [
            'id' => $item->id,
            'title' => $item->title,
            'type' => $item->type,
            'href' => $href,
            'extensionId' => $item->extensionId,
        ];
    }

    private function resolvePageUrl(MenuItem $item): ?string
    {
        if ($item->pageId !== null) {
            $page = PageRecord::query()
                ->whereKey($item->pageId)
                ->where('status', 'published')
                ->first();

            if ($page !== null) {
                return '/' . ltrim((string) $page->slug, '/');
            }
        }

        if ($item->slug !== null && $item->slug !== '') {
            $page = PageRecord::query()
                ->where('slug', $item->slug)
                ->where('status', 'published')
                ->first();

            return $page ? '/' . ltrim((string) $page->slug, '/') : null;
        }

        return null;
    }
}
