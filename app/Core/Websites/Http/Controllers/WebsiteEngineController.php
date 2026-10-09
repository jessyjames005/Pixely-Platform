<?php

declare(strict_types=1);

namespace App\Core\Websites\Http\Controllers;

use App\Core\Websites\Contracts\WebsiteEngineInterface;
use App\Core\Websites\Http\Requests\StoreMenuRequest;
use App\Core\Websites\Http\Requests\StorePageRequest;
use App\Core\Websites\Http\Requests\UpdateMenuRequest;
use App\Core\Websites\Http\Requests\UpdatePageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * API controller for the Website Engine.
 *
 * Provides REST endpoints for managing website pages and menus.
 */
final class WebsiteEngineController extends Controller
{
    public function __construct(
        private WebsiteEngineInterface $websiteEngine,
    ) {
    }

    /**
     * Display all pages.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->query();
        $pages = $this->websiteEngine->listPages($filters);

        return response()->json([
            'data' => $pages,
            'meta' => [
                'total' => count($pages),
                'page' => $request->query('page', 1),
                'per_page' => $request->query('per_page', 20),
            ],
        ]);
    }

    /**
     * Display a specific page.
     */
    public function show(string $slug): JsonResponse
    {
        $page = $this->websiteEngine->getPage($slug);

        if (! $page) {
            return response()->json([
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json(['data' => $page]);
    }

    /**
     * Create a new page.
     */
    public function store(StorePageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $page = $this->websiteEngine->createPage($data);

        return response()->json(['data' => $page], 201);
    }

    /**
     * Update an existing page.
     */
    public function update(string $id, UpdatePageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $page = $this->websiteEngine->updatePage($id, $data);

        return response()->json(['data' => $page]);
    }

    /**
     * Delete a page.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->websiteEngine->deletePage($id);

        return response()->json(['message' => 'Page deleted']);
    }

    /**
     * Display all menus.
     */
    public function listMenus(Request $request): JsonResponse
    {
        $menus = $this->websiteEngine->getAllMenus($request->query());

        return response()->json(['data' => $menus]);
    }

    /**
     * Display a specific menu.
     */
    public function showMenu(string $code): JsonResponse
    {
        $menu = $this->websiteEngine->getMenu($code);

        if (! $menu) {
            return response()->json([
                'message' => 'Menu not found',
            ], 404);
        }

        return response()->json(['data' => $menu]);
    }

    /**
     * Create a new menu.
     */
    public function storeMenu(StoreMenuRequest $request): JsonResponse
    {
        $data = $request->validated();
        $menu = $this->websiteEngine->createMenu($data);

        return response()->json(['data' => $menu], 201);
    }

    /**
     * Update an existing menu.
     */
    public function updateMenu(string $id, UpdateMenuRequest $request): JsonResponse
    {
        $data = $request->validated();
        $menu = $this->websiteEngine->updateMenu($id, $data);

        return response()->json(['data' => $menu]);
    }

    /**
     * Delete a menu.
     */
    public function destroyMenu(string $id): JsonResponse
    {
        $this->websiteEngine->deleteMenu($id);

        return response()->json(['message' => 'Menu deleted']);
    }
}
