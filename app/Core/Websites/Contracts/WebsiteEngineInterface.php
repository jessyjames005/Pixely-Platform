<?php


declare(strict_types=1);

namespace App\Core\Websites\Contracts;

use App\Core\Websites\Models\PageModel;
use App\Core\Websites\Models\Menu;

/**
 * Base contract for the Website Engine.
 *
 * Manages pages, menus and their associations for Website/User surfaces.
 */
interface WebsiteEngineInterface
{
    /**
     * Create a new page.
     *
     * @param array<string, mixed> $data Page data (title, slug, template, etc.)
     * @return PageModel The created page
     */
    public function createPage(array $data): PageModel;

    /**
     * Update an existing page.
     *
     * @param string $id Unique page identifier
     * @param array<string, mixed> $data Updated data
     * @return PageModel The updated page
     */
    public function updatePage(string $id, array $data): PageModel;

    /**
     * Delete a page.
     */
    public function deletePage(string $id): void;

    /**
     * Retrieve a page by its slug.
     *
     * @param string $slug Unique page slug
     * @return PageModel|null Found page or null
     */
    public function getPage(string $slug): ?PageModel;

    /**
     * List pages with optional filters.
     *
     * @param array<string, mixed> $filters Search filters (status, template, etc.)
     * @return PageModel[] List of matching pages
     */
    public function listPages(array $filters = []): array;

    /**
     * Create a navigation menu.
     *
     * @param array<string, mixed> $data Menu data (name, code, items)
     * @return Menu The created menu
     */
    public function createMenu(array $data): Menu;

    /**
     * Update an existing menu.
     *
     * @param string $id Unique menu identifier
     * @param array<string, mixed> $data Updated data
     * @return Menu The updated menu
     */
    public function updateMenu(string $id, array $data): Menu;

    /**
     * Delete a menu.
     */
    public function deleteMenu(string $id): void;

    /**
     * Retrieve a menu by its code.
     *
     * @param string $code Unique menu code (homepage, footer, sidebar)
     * @return Menu|null Found menu or null
     */
    public function getMenu(string $code): ?Menu;

    /**
     * List all menus with optional filters.
     *
     * @param array<string, mixed> $filters Search filters
     * @return array<int, Menu> List of matching menus
     */
    public function getAllMenus(array $filters = []): array;
}