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

### TODOs (Ready for next sprints)

- **Database persistence:** Retrieve/save pages and menus
- **Validation systems:** Validate slugs, URLs, types
- **Association services:** Link menus to permissions and surfaces
- **Page templates:** System for different page types
- **Integration route:** Connect WebsiteEngine data to Laravel route system
- **Permission system:** Integrate with existing permission system

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

## Service Command

### Creating the Service Provider
```bash
php artisan vendor:publish --provider="App\Core\Websites\Providers\WebsiteEngineServiceProvider" --tag="website-engine"
```

### Registering routes
Routes can be added using standard Laravel route files:
```php
// routes/web.php
Route::get('/', function () {
    return view('website.home');
})->name('website.home');

Route::get('/about', function () {
    return view('website.page', ['slug' => 'about']);
})->name('website.page');
```

## Security and Permissions

### Access Controls
- **Route middleware:** Authentication and permission verification
- **Ownership verification:** Users can only modify their own pages
- **Surface permissions:** Different permissions for public vs user vs admin

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
1. Foundation Website/Core mechanisms established
2. Ready for `/` and `/account` routes
3. Structure ready for Page Builder
4. Documentation for future development

### Business Impact
- **Website surface established:** Public website accessible to users
- **Unified navigation:** Cohesive menus and routes
- **Extensible foundation:** Ready for extensions and blocks
- **Maintainable architecture:** Clear contracts and separated services

---

This sprint establishes the essential foundation for website surfaces, providing the infrastructure for a robust, extensible, and organized website system that can evolve with the application's needs.

**The Website Engine Foundation is complete and ready for the next development phase.** 🚀