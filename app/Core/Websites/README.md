# Website Engine Foundation - Documentation

## Overview
This sprint establishes the foundation for website (public) and user space surfaces by providing the models, contracts, and services essential for managing pages, menus, and website content.

## Website Engine (Core)

### Contracts
- **WebsiteEngineInterface** — Main contract for all website engine operations
- **PageModel** — Data model for website pages
- **MenuItem** — Model for individual menu items
- **Menu** — Model for groups of navigation menus

### Services
- **WebsiteEngine** — Core service implementing the WebsiteEngineInterface contract
- **WebsiteEngineServiceProvider** — Provides the WebsiteEngine service via Laravel's service container

## Models

### PageModel
- **id** — Unique identifier for the page
- **slug** — URL-friendly identifier (automatically generated)
- **title** — Page title
- **status** — draft|published|archived
- **template** — default|homepage|article|contact|etc.
- **seo** — SEO metadata (title, description, keywords, og:image)
- **blocks** — Page content (extensible blocks)

### MenuItem
- **id** — Unique identifier for the menu item
- **type** — page|extension|external
- **title** — Menu item label
- **targetUrl** — Target URL (internal or external)
- **pageId** — Reference to page ID (if page type)
- **extensionId** — Reference to extension ID (if extension type)
- **slug** — Slug (for pages)
- **sortOrder** — Sort order within menu
- **active** — Visibility flag

### Menu
- **id** — Unique identifier for the menu
- **name** — Short name for the menu
- **code** — Menu code (homepage, footer, sidebar, etc.)
- **items** — Array of MenuItem objects

## Interface

### WebsiteEngineInterface
Main methods for page and menu management:

- `createPage(array $data): PageModel`
- `updatePage(string $id, array $data): PageModel`

- `createMenu(array $data): Menu`
- `updateMenu(string $id, array $data): Menu`

## Current Constraints

### Basic Validation
- **Slugs automatically generated** from titles
- **Unique IDs** generated for new records
- **Valid menu item types** (page/extension/external)

### Current foundation

- **Database persistence:** Implemented through `PageRecord`, `MenuRecord` and `MenuItemRecord`.
- **Validation:** Implemented through dedicated page and menu `FormRequest` classes.
- **Permissions:** Management endpoints require the `website.*` permissions and the API surface.
- **Administration UI:** Pages and menus are managed from `/admin/website/pages` and `/admin/website/menus`.
- **Page templates:** The `template` field is persisted and ready for the future rendering/theme system.

### Next phases

- **Association services:** Link menus to extension capabilities and surfaces.
- **JSON:API resources:** Align management endpoints with the platform JSON:API convention.
- **Public rendering:** Resolve published pages and menus on the public surface.
- **Page composition:** Introduce typed blocks without coupling the Core to presentation-specific components.

## Migration Phases

### Phase 2: Website Engine (Completed)
- Page + Menu + Theme foundation established
- Ready for `/` (public) and `/account` (user) routes
- Website surface foundation established

### Phase 3: Page Builder (Coming Soon)
- Add drag-and-drop for page creation
- Extend block system (`PageModel.blocks`)
- Implement page templates

### Phase 4: Extensions Web
- Extend existing extensions (Gallery, Files) with website routes
- Integrate extension content into website pages
- Add extension widgets

## Registration

The provider is registered in `bootstrap/providers.php` and loads the
management API routes under `api/v1/website` (same per-module convention
as Auth, Users, Roles, Extensions and Tooling):

```text
GET    /api/v1/website/pages          website.pages.view
GET    /api/v1/website/pages/{slug}   website.pages.view
POST   /api/v1/website/pages          website.pages.manage
PUT    /api/v1/website/pages/{id}     website.pages.manage
DELETE /api/v1/website/pages/{id}     website.pages.manage
GET    /api/v1/website/menus          website.menus.view
GET    /api/v1/website/menus/{code}   website.menus.view
POST   /api/v1/website/menus          website.menus.manage
PUT    /api/v1/website/menus/{id}     website.menus.manage
DELETE /api/v1/website/menus/{id}     website.menus.manage
```

Every management route requires `auth:sanctum` + `surface:api` + the listed permission. Resource-level policy checks remain the final authorization layer.

## Database

The schema lives in `database/migrations` (Core convention):

- `website_pages` — string `id` primary key, unique `slug`, `status`, `template`, `seo`, `blocks`
- `website_menus` — string `id` primary key, unique `code`
- `website_menu_items` — string `id`, FK `menu_id` → `website_menus` (cascade), `type` (page/extension/external), `page_id`, `extension_id`, `sort_order`, `active`

Identifiers are strings and are generated with stable `page_...`, `menu_...` and
`menu_item_...` prefixes. Eloquent persistence models are kept separate from
the framework-light domain DTOs.

## Security and Permissions

### Access Controls
- **Route middleware:** `auth:sanctum` + `permission:website.*` on every route
- **Permissions:** `website.pages.view`, `website.pages.manage`, `website.menus.view`, `website.menus.manage` (seeded as core in `RolePermissionSeeder`)
- **Surface targeting:** the engine serves the `public` and `user` surfaces (see ADR-0090)

### Error Handling
- **Standard error codes:** JSON REST error envelope
- **User-friendly messages:** Frontend feedback
- **Logging:** Centralized error and audit logging

## Future Expansion

### Website Engine Features
- **Dynamic views:** Vue rendered via Blade
- **Content management:** Full CRUD for pages and menus
- **Block systems:** Extensible to different block types
- **Interface customization:** Themes and layouts based on user preferences

### Extension Integration
- **Extension-specific routes:** Integrate extension routes into website
- **Extension content:** Display extension content in website pages
- **Extension widgets:** Integrate extension widgets

## Sprint Summary

### Deliverables Produced
1. Contracts WebsiteEngineInterface, PageModel, MenuItem, Menu
2. WebsiteEngine service with basic CRUD operations
3. Service Provider and route registration
4. Complete documentation

### Ready for the Next Phase
1. Persistence-backed page and menu CRUD is established
2. API authentication, surface and permissions are enforced
3. Public and user experiences can now be built on the same engine
4. Theme/layout and navigation remain intentionally deferred

### Business Impact
- **Website foundation established:** Pages and navigation are now persistence-backed
- **Unified navigation:** Cohesive menus and routes
- **Extensible foundation:** Ready for extensions and blocks
- **Maintainable architecture:** Clear contracts and separated services

---

This sprint establishes the essential foundation for website surfaces, providing the infrastructure for a robust, extensible, and organized website system that can evolve with the application's needs.

**The Website Engine Foundation is complete and ready for the next development phase.**