<?php


declare(strict_types=1);

namespace App\Core\Websites\Services;

use App\Core\Websites\Contracts\WebsiteEngineInterface;
use App\Core\Websites\Models\PageModel;
use App\Core\Websites\Models\Menu;
use App\Core\Websites\Models\MenuItem;

/**
 * Core Website Engine service.
 *
 * Implements the WebsiteEngineInterface to manage pages,
 * menus and their associations.
 * Provides the essential pipeline for the website without drag-and-drop MVP.
 */
final class WebsiteEngine implements WebsiteEngineInterface
{
    /**
     * {@inheritdoc}
     */
    public function createPage(array $data): PageModel
    {
        // TODO: Validate page data
        // TODO: Generate unique slug if not provided
        // TODO: Validate specified template
        // TODO: Persist page in database

        return new PageModel(
            id: $data['id'] ?? $this->generatePageId(),
            slug: $data['slug'] ?? $this->generateSlug($data['title'] ?? ''),
            title: $data['title'] ?? '',
            status: $data['status'] ?? 'draft',
            template: $data['template'] ?? 'default',
            seo: $data['seo'] ?? [],
            blocks: $data['blocks'] ?? [],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updatePage(string $id, array $data): PageModel
    {
        // TODO: Retrieve existing page
        // TODO: Validate update data
        // TODO: Apply changes
        // TODO: Persist updates

        // Temporary implementation - returns a new page
        return new PageModel(
            id: $id,
            slug: $data['slug'] ?? '',
            title: $data['title'] ?? '',
            status: $data['status'] ?? 'draft',
            template: $data['template'] ?? 'default',
            seo: $data['seo'] ?? [],
            blocks: $data['blocks'] ?? [],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deletePage(string $id): void
    {
        // TODO: Delete page from database
        // TODO: Clean up associated data
    }

    /**
     * {@inheritdoc}
     */
    public function getPage(string $slug): ?PageModel
    {
        // TODO: Retrieve page by slug from database
        // TODO: Return null if not found

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function listPages(array $filters = []): array
    {
        // TODO: Apply filters (status, template, etc.)
        // TODO: Retrieve pages from database

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function createMenu(array $data): Menu
    {
        // TODO: Validate menu data
        // TODO: Create menu items
        // TODO: Persist menu

        $items = array_map(fn($item) => new MenuItem(
            id: $item['id'],
            type: $item['type'],
            title: $item['title'],
            targetUrl: $item['targetUrl'] ?? null,
            pageId: $item['pageId'] ?? null,
            extensionId: $item['extensionId'] ?? null,
            slug: $item['slug'] ?? null,
            sortOrder: $item['sortOrder'] ?? 0,
            active: $item['active'] ?? true,
        ), $data['items'] ?? []);

        return new Menu(
            id: $data['id'] ?? $this->generateMenuId(),
            name: $data['name'] ?? '',
            code: $data['code'] ?? '',
            items: $items,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updateMenu(string $id, array $data): Menu
    {
        // TODO: Retrieve existing menu
        // TODO: Apply updates
        // TODO: Persist changes

        // Temporary implementation
        return new Menu(
            id: $id,
            name: $data['name'] ?? '',
            code: $data['code'] ?? '',
            items: [],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMenu(string $id): void
    {
        // TODO: Delete menu from database
    }

    /**
     * {@inheritdoc}
     */
    public function getMenu(string $code): ?Menu
    {
        // TODO: Retrieve menu by code from database

        return null;
    }

    /**
     * Retrieve all menus with optional filters.
     */
    public function getAllMenus(array $filters = []): array
    {
        // TODO: Implement menu retrieval with filters
        // For now, return an empty array
        return [];
    }

    /**
     * Generate a unique identifier for a page.
     */
    private function generatePageId(): string
    {
        return 'page_' . uniqid();
    }

    /**
     * Generate a slug from a title.
     */
    private function generateSlug(string $title): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $title)));
    }

    /**
     * Generate a unique identifier for a menu.
     */
    private function generateMenuId(): string
    {
        return 'menu_' . uniqid();
    }
}