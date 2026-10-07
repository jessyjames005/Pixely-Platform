<?php

declare(strict_types=1);

namespace App\Core\Websites\Contracts;

use App\Core\Websites\Models\Menu;
use App\Core\Websites\Models\PageModel;

/**
 * Application contract for website content management.
 */
interface WebsiteEngineInterface
{
    public function createPage(array $data): PageModel;
    public function updatePage(string $id, array $data): PageModel;
    public function deletePage(string $id): void;
    public function getPage(string $slug): ?PageModel;
    public function listPages(array $filters = []): array;

    public function createMenu(array $data): Menu;
    public function updateMenu(string $id, array $data): Menu;
    public function deleteMenu(string $id): void;
    public function getMenu(string $code): ?Menu;
    public function getAllMenus(array $filters = []): array;
}
