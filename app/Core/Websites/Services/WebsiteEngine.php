<?php

declare(strict_types=1);

namespace App\Core\Websites\Services;

use App\Core\Websites\Contracts\WebsiteEngineInterface;
use App\Core\Websites\Models\Menu;
use App\Core\Websites\Models\MenuItem;
use App\Core\Websites\Models\PageModel;
use App\Core\Websites\Persistence\Models\MenuRecord;
use App\Core\Websites\Persistence\Models\PageRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use App\Core\Websites\Persistence\Models\MenuItemRecord;

/**
 * Website Engine application service.
 *
 * Keeps the domain DTOs independent from Eloquent while providing the
 * persistence-backed page and menu operations required by the platform.
 */
final class WebsiteEngine implements WebsiteEngineInterface
{
    private const PAGE_STATUSES = ['draft', 'published', 'archived'];

    public function createPage(array $data): PageModel
    {
        $title = $this->requiredString($data, 'title');
        $slug = $this->uniquePageSlug((string) ($data['slug'] ?? $title));
        $status = $this->pageStatus($data['status'] ?? 'draft');

        $record = PageRecord::query()->create([
            'id' => $data['id'] ?? 'page_' . Str::uuid(),
            'slug' => $slug,
            'title' => $title,
            'status' => $status,
            'template' => (string) ($data['template'] ?? 'default'),
            'seo' => $data['seo'] ?? [],
            'blocks' => $data['blocks'] ?? [],
        ]);

        return $this->toPage($record);
    }

    public function updatePage(string $id, array $data): PageModel
    {
        $record = PageRecord::query()->findOrFail($id);

        if (array_key_exists('slug', $data)) {
            $data['slug'] = $this->uniquePageSlug((string) $data['slug'], $id);
        }

        if (array_key_exists('status', $data)) {
            $data['status'] = $this->pageStatus($data['status']);
        }

        $record->fill(array_intersect_key($data, array_flip([
            'slug', 'title', 'status', 'template', 'seo', 'blocks',
        ])));
        $record->save();

        return $this->toPage($record->refresh());
    }

    public function deletePage(string $id): void
    {
        PageRecord::query()->findOrFail($id)->delete();
    }

    public function getPage(string $slug): ?PageModel
    {
        $record = PageRecord::query()->where('slug', $slug)->first();

        return $record ? $this->toPage($record) : null;
    }

    public function getPublishedPage(string $slug): ?PageModel
    {
        $record = PageRecord::query()
            ->where('slug', trim($slug, '/'))
            ->where('status', 'published')
            ->first();

        return $record ? $this->toPage($record) : null;
    }

    public function listPages(array $filters = []): array
    {
        $query = PageRecord::query();

        foreach (['status', 'template'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('title')->get()->map(fn (PageRecord $record): PageModel => $this->toPage($record))->all();
    }

    public function createMenu(array $data): Menu
    {
        $name = $this->requiredString($data, 'name');
        $code = $this->requiredString($data, 'code');

        $menu = DB::transaction(function () use ($data, $name, $code): MenuRecord {
            $record = MenuRecord::query()->create([
                'id' => $data['id'] ?? 'menu_' . Str::uuid(),
                'name' => $name,
                'code' => $code,
            ]);

            $this->replaceMenuItems($record, $data['items'] ?? []);

            return $record->load('items');
        });

        return $this->toMenu($menu);
    }

    public function updateMenu(string $id, array $data): Menu
    {
        $menu = DB::transaction(function () use ($id, $data): MenuRecord {
            $record = MenuRecord::query()->findOrFail($id);
            $record->fill(array_intersect_key($data, array_flip(['name', 'code'])));
            $record->save();

            if (array_key_exists('items', $data)) {
                $this->replaceMenuItems($record, $data['items']);
            }

            return $record->load('items');
        });

        return $this->toMenu($menu);
    }

    public function deleteMenu(string $id): void
    {
        MenuRecord::query()->findOrFail($id)->delete();
    }

    public function getMenu(string $code): ?Menu
    {
        $record = MenuRecord::query()->with('items')->where('code', $code)->first();

        return $record ? $this->toMenu($record) : null;
    }

    public function getAllMenus(array $filters = []): array
    {
        $query = MenuRecord::query()->with('items');

        if (! empty($filters['code'])) {
            $query->where('code', $filters['code']);
        }

        return $query->orderBy('name')->get()->map(fn (MenuRecord $record): Menu => $this->toMenu($record))->all();
    }

    private function replaceMenuItems(MenuRecord $menu, array $items): void
    {
        $menu->items()->delete();

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Each menu item must be an object.');
            }

            $menu->items()->create([
                'id' => $item['id'] ?? 'menu_item_' . Str::uuid(),
                'type' => (string) ($item['type'] ?? 'page'),
                'title' => $this->requiredString($item, 'title'),
                'target_url' => $item['targetUrl'] ?? null,
                'page_id' => $item['pageId'] ?? null,
                'extension_id' => $item['extensionId'] ?? null,
                'slug' => $item['slug'] ?? null,
                'sort_order' => (int) ($item['sortOrder'] ?? $index),
                'active' => (bool) ($item['active'] ?? true),
            ]);
        }
    }

    private function uniquePageSlug(string $value, ?string $ignoreId = null): string
    {
        $slug = Str::slug($value);

        if ($slug === '') {
            throw new InvalidArgumentException('The page slug cannot be empty.');
        }

        $base = $slug;
        $suffix = 2;

        while (PageRecord::query()
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    private function pageStatus(mixed $status): string
    {
        $status = (string) $status;

        if (! in_array($status, self::PAGE_STATUSES, true)) {
            throw new InvalidArgumentException('Unsupported page status: ' . $status);
        }

        return $status;
    }

    private function requiredString(array $data, string $key): string
    {
        $value = trim((string) ($data[$key] ?? ''));

        if ($value === '') {
            throw new InvalidArgumentException('The ' . $key . ' field is required.');
        }

        return $value;
    }

    private function toPage(PageRecord $record): PageModel
    {
        return new PageModel(
            id: (string) $record->id,
            slug: (string) $record->slug,
            title: (string) $record->title,
            status: (string) $record->status,
            template: (string) $record->template,
            seo: $record->seo ?? [],
            blocks: $record->blocks ?? [],
        );
    }

    private function toMenu(MenuRecord $record): Menu
    {
        return new Menu(
            id: (string) $record->id,
            name: (string) $record->name,
            code: (string) $record->code,
            items: $record->items->map(fn (MenuItemRecord $item): MenuItem => new MenuItem(
                id: (string) $item->id,
                type: (string) $item->type,
                title: (string) $item->title,
                targetUrl: $item->target_url,
                pageId: $item->page_id,
                extensionId: $item->extension_id,
                slug: $item->slug,
                sortOrder: (int) $item->sort_order,
                active: (bool) $item->active,
            ))->all(),
        );
    }
}
