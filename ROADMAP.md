# Pixely Platform Roadmap

This roadmap defines the planned evolution of Pixely Platform from the initial platform foundation to a stable, extensible platform with a complete administration interface, developer tooling and multiple example extensions.

---

## v0.1.0 - Foundation

### Project & Documentation

* [x] Project vision
* [x] Project README
* [x] Project roadmap
* [x] Project changelog
* [x] Architecture documentation
* [x] Architecture Decision Records (ADR)
* [x] Documentation structure
* [x] Sprint documentation
* [x] GitHub technical documentation

### Development Environment

* [x] Windows development environment
* [x] PHP development environment
* [x] Composer
* [x] Laravel
* [x] Node.js / NPM
* [x] VS Code development environment
* [x] Docker
* [x] Docker Compose
* [x] WSL / Linux development support

### Git Workflow

* [x] Git repository
* [x] GitHub repository
* [x] `develop` branch
* [x] Feature branch workflow
* [x] SSH authentication
* [x] Remote repository configuration
* [x] Commit workflow
* [x] Push workflow

### CI/CD

* [ ] Continuous integration
* [ ] Automated test execution
* [ ] Static analysis
* [ ] Code style checks
* [ ] Continuous deployment

---

## v0.2.0 - Kernel

### Extension Architecture

The original Module concept evolved into the Pixely Extension architecture.

* [x] Extension contract
* [x] Extension manifest
* [x] Extension registry
* [x] Extension discovery
* [x] Extension repository
* [x] Extension manager
* [x] Extension state
* [x] Extension status
* [x] Extension registration
* [x] Extension boot process
* [x] Extension enable / disable lifecycle
* [x] Extension dependency declaration
* [x] Extension dependency validation
* [x] Circular dependency detection
* [x] Extension state persistence

### Extension Configuration

* [x] `ExtensionConfigurableInterface`
* [x] Default extension configuration
* [x] Configuration access
* [x] Configuration overrides
* [x] Configuration persistence
* [x] Configuration existence checks
* [x] Configuration removal
* [x] Configuration tests

### Extension Commands

* [x] Extension enable command
* [x] Extension disable command
* [x] Persistent extension state
* [x] Extension command tests

### Kernel Tests

* [x] Unit tests for extension contracts
* [x] Unit tests for extension configuration
* [x] Unit tests for extension persistence
* [x] Feature tests for extension lifecycle
* [x] Feature tests for extension commands
* [x] Dependency validation tests

---

## v0.3.0 - Core

### Authentication

* [x] Authentication
* [x] Login
* [x] Logout
* [ ] Password management
* [x] Authentication API
* [ ] Two-factor authentication (2FA)
* [ ] Forgot password / password reset flow
* [ ] "Remember me" persistent session

### Users

* [x] User model
* [x] User management
* [ ] User profile
* [ ] User preferences

#### User profile (planned)

* [ ] Profile fields: display name, bio/title, timezone
* [ ] Avatar upload, stored via the Files Extension (dependency) rather than ad-hoc upload handling
* [ ] "My profile" self-service screen, distinct from admin-only Users management

#### User preferences (planned)

* [ ] Per-user preference storage (theme choice, density, notification opt-outs), separate table/model from Core Settings' UserSetting (which is locale-only today) — or extend UserSetting's JSON blob if scope stays small

### Roles & Permissions

* [x] Role system
* [x] Permission system
* [x] Role assignment
* [x] Permission checks
* [x] Extension-declared permissions (dynamic, not hardcoded)

#### Extension-declared permissions (reference: Mediboard rights screen, reviewed 2026-08)

Today, permissions are hardcoded in RolePermissionSeeder. The target model: each installed extension declares its own permission set (view/manage/delete per object, following the established naming convention), synced into the database automatically on install/enable — no more manually editing a seeder for every new extension or object type.

* [ ] Extension manifest/contract method declaring the extension's permissions (object → available actions)
* [ ] Automatic permission sync on extension install/enable (create missing Permission rows; never silently delete existing ones on disable/uninstall, to avoid breaking existing role assignments)
* [ ] Roles UI: per-module/per-object row, not a raw permission checkbox list
* [ ] Roles UI: friendly two-column control per row — "Accessibilité" (Interdit / Lecture / Écriture, mapping to none / view / manage+delete) and "Visibilité" (Caché / Visible, controlling whether the admin nav section appears at all for that role)
* [ ] "Droits existants" summary view: read-only table of every module/profile/user combination and its current access level, for auditing at a glance

### Settings

* [x] Platform settings
* [x] User settings
* [ ] Extension settings
* [x] Persistent settings storage

#### Extension settings (planned)

* [ ] Per-extension settings screen in the admin (distinct from the raw JSON config editor already in Extension Manager — a friendlier, field-typed UI once an extension declares a settings schema)
* [ ] Extension manifest/contract method declaring settings fields (key, type, label, default) — mirrors the planned extension-declared permissions approach

### Localization

* [x] Translation system
* [x] Locale management
* [x] Language selection
* [ ] Extension translations

---

## v0.4.0 - Administration Foundation

The Administration layer provides the first visual interface for managing the Pixely Platform.

### Administration Architecture

* [x] Administration frontend architecture
* [x] Vue.js 3 integration
* [x] TypeScript integration
* [x] Vue Router integration
* [x] API client architecture
* [x] Authentication integration
* [x] Administration layout
* [x] Administration navigation
* [x] Administration route protection

### Material Design System

* [x] Material Design principles
* [x] Vuetify integration
* [x] Pixely Design System
* [x] Design tokens (via Vuetify theme)
* [ ] Typography system (au-delà des defaults Vuetify)
* [ ] Spacing system (au-delà des defaults Vuetify)
* [ ] Icon system
* [x] Colour system
* [x] Theme system
* [x] Light theme
* [x] Dark theme
* [ ] Responsive design rules
* [ ] Accessibility rules
* [ ] Dedicated visual identity for the System/Tooling/Administration area (black, electric blue, white palette), distinct from the general platform theme

### UI Design & Prototyping

* [ ] UI/UX design workflow
* [ ] Figma or equivalent free design tool
* [ ] Administration wireframes
* [ ] Administration layout mockups
* [ ] Design System documentation
* [ ] Component specifications
* [ ] Responsive mockups
* [ ] Dark mode mockups
* [ ] Pixely UI/UX Kit based on Material Design 3
* [ ] Pixely UI/UX Kit foundations: colour, typography, spacing, elevation, shape, icons and motion
* [ ] Pixely UI/UX Kit components and states
* [ ] Pixely UI/UX Kit patterns: navigation, dashboards, forms, tables, dialogs, notifications and empty/error states
* [ ] Pixely UI/UX Kit responsive and dark-mode specifications
* [ ] Pixely UI/UX Kit documentation and usage guidelines
* [ ] Example web templates for Pixely administration and extension UIs
* [ ] Figma reference: Tailwind Admin Dashboard UI Kit
  - https://www.figma.com/design/WrrGik3XDPZuPSXIqruw59/Free-Figma-Admin-Dashboard-UI-Kit---Tailwind-Admin--Community-?m=auto&is-community-duplicate=1&fuid=1677103082960936720
* [ ] Figma reference: Vision UI Dashboard — React + MUI
  - https://www.figma.com/design/1CWikkF7YdXsh4bFYlaSog/Vision-UI-Dashboard-React---MUI-Dashboard--Free-Version---Community-?m=auto&is-community-duplicate=1&fuid=1677103082960936720
* [ ] UI/UX reference: Staffu
  - https://staffu.mantrakshdevs.com/documentation/

### UI Components

* [x] Buttons (v-btn)
* [x] Inputs (v-text-field)
* [x] Selects (v-select)
* [ ] Checkboxes
* [ ] Radio buttons
* [ ] Switches
* [ ] Dialogs
* [x] Alerts (v-alert)
* [ ] Notifications
* [x] Cards (v-card)
* [x] Tables (v-data-table)
* [x] Pagination (v-pagination)
* [ ] Tabs
* [ ] Breadcrumbs
* [x] Loading states (built into v-btn/v-data-table)
* [ ] Empty states
* [ ] Error states

### Storybook

Storybook provides an isolated environment for developing and documenting Vue.js components.

* [ ] Storybook installation
* [ ] Storybook Vue.js integration
* [ ] Vuetify integration
* [ ] Pixely Design System integration
* [ ] Component stories
* [ ] Component documentation
* [ ] Interactive component examples
* [ ] Accessibility checks
* [ ] Responsive component previews
* [ ] Storybook development workflow

### UI Design & Prototyping

* [ ] UI/UX design workflow
* [ ] Figma or equivalent free design tool
* [ ] Administration wireframes
* [ ] Administration layout mockups
* [ ] Design System documentation
* [ ] Component specifications
* [ ] Responsive mockups
* [ ] Dark mode mockups

### Dashboard

* [ ] Administration dashboard
* [ ] System status
* [ ] Extension status
* [ ] Platform statistics
* [ ] Dashboard widgets
* [ ] Recent activity
* [ ] Quick actions

---

## v0.5.0 - Extension SDK

The Extension SDK provides a stable foundation for building independent Pixely extensions.

### Extension Structure

* [x] Extension manifest
* [x] Extension contract
* [x] Extension service providers
* [x] Extension routes
* [x] Extension API routes
* [x] Extension models
* [x] Extension controllers

### Extension Lifecycle

* [x] Registration
* [x] Discovery
* [x] Boot
* [x] Enable
* [x] Disable
* [x] Dependency validation
* [x] State persistence

### Configuration

* [x] Default configuration
* [x] Configuration overrides
* [x] Configuration persistence
* [x] Configuration access
* [x] Configuration removal

### Database

* [ ] Extension migrations
* [ ] Migration versioning
* [ ] Extension database isolation
* [ ] Migration rollback
* [ ] Migration status
* [ ] Migration compatibility checks

### Assets

* [ ] Extension assets
* [ ] Asset publishing
* [ ] Frontend assets
* [ ] Extension views
* [ ] Extension Vue components
* [ ] Extension frontend routes

### Versioning

* [x] Extension manifest version
* [x] Extension version compatibility
* [ ] Platform / extension compatibility rules
* [x] Extension upgrade mechanism

#### Incremental upgrade mechanism (planned — replaces "update = re-upload full zip")

Today, `update()` replaces an extension's entire directory from a new zip. The target model: an extension ships versioned upgrade steps, applied incrementally from the currently installed version up to the target version — closer to database migrations than a full package swap.

* [x] Extension declares an ordered list of version steps
* [x] Example step semantics: bugfix step (no schema change) vs schema-changing step (add columns + backfill)
* [x] Installed version tracked separately from manifest version (`extension_installed_versions`)
* [x] Update flow applies only pending steps between installed and target version, each in its own transaction, partial success preserved on failure
* [x] "Update available" detection via semver comparison (reject update if package version ≤ installed version)
* [x] Full-zip-replace flow remains for new installs; incremental steps for updates

### Developer Experience

* [ ] Extension generator — CLI command (`php artisan make:extension <name>`) scaffolding an empty, valid extension: `extension.php` manifest, main class implementing `ExtensionInterface`, `Providers/`, `Http/Controllers/`, `routes/api.php`, `Database/Migrations/`, `resources/js/{store,models,views,tests}` following the established domain-driven frontend structure
* [ ] Extension development template
* [ ] Extension testing helpers
* [ ] Extension SDK documentation
* [ ] Extension development guidelines
* [ ] Extension frontend guidelines
* [ ] Extension Design System guidelines
* [ ] Extension Storybook guidelines

---

## v0.6.0 - Platform API

### API Foundation

* [x] API route architecture
* [x] Extension-owned API routes
* [x] API middleware
* [x] JSON API responses
* [x] API versioning
* [x] API error format
* [x] API query parser
* [x] API query applier
* [x] API pagination
* [x] API filtering
* [x] API sorting
* [x] API relationships / includes

### REST API

* [x] Gallery listing endpoint
* [x] Gallery detail endpoint
* [x] Gallery upload endpoint
* [x] Gallery update endpoint
* [x] Gallery delete endpoint
* [x] Generic extension API conventions
* [x] API pagination
* [x] API filtering
* [x] API sorting
* [ ] API relationship documentation
* [ ] API validation standardisation

### JSON:API Migration (future)

The current API response format (`{ data }`, `{ data, meta }`, `{ error }`) is stable and used across the frontend. A future migration to strict JSON:API compliance is planned but deferred to avoid blocking frontend progress.

* [ ] JSON:API resource object format (`type`, `id`, `attributes`, `relationships`)
* [ ] JSON:API error array format (`errors[]` with `status`, `code`, `title`, `detail`, `source`)
* [ ] JSON:API `links` (self, pagination `next`/`prev`)
* [ ] `Content-Type: application/vnd.api+json` content negotiation
* [ ] Rewrite `ApiResponse` / `ApiCollectionResponse` / `ApiError` for JSON:API
* [ ] Update `openapi.yml` schemas for JSON:API
* [ ] Update frontend API client and types for JSON:API
* [ ] Migration tests

### API Authentication

* [ ] API authentication
* [ ] API tokens
* [ ] Token permissions
* [ ] Extension API permissions
* [ ] Personal access token management UI (create, name, scope, revoke, last-used tracking)

### OpenAPI

OpenAPI documentation is maintained through extension-owned API definitions and generated centrally by the platform.

Each extension provides its own OpenAPI API definition. The platform collects these definitions, validates them, and generates the public OpenAPI specification.

The generated docs/api/openapi.yml is an output artifact and must not be maintained manually.

* [x] OpenAPI specification structure
* [x] Initial openapi.yml
* [x] Extension-owned OpenAPI API definitions
* [x] Extension OpenAPI definition discovery
* [x] Extension OpenAPI definition validation
* [x] OpenAPI path validation
* [x] OpenAPI operation validation
* [x] Required operationId validation
* [x] Required responses validation
* [x] Duplicate OpenAPI path detection
* [x] Duplicate operationId detection
* [x] Merge extension OpenAPI definitions
* [x] Generate openapi.yml
* [x] Validate generated openapi.yml
* [x] OpenAPI validation Artisan command
* [x] Automated OpenAPI generation tests
* [x] Automated OpenAPI validation tests
* [ ] OpenAPI relationship documentation
* [ ] OpenAPI authentication documentation
* [ ] OpenAPI examples
* [ ] Document OpenAPI generation workflow
* [ ] Document extension OpenAPI definition workflow

### Swagger UI

Swagger UI is the primary interactive interface for exploring and testing the generated API documentation.

* [ ] Swagger UI integration
* [ ] Swagger UI Docker integration
* [ ] Connect Swagger UI to generated `openapi.yml`
* [ ] Interactive API exploration
* [ ] API request testing
* [ ] Authentication testing
* [ ] Swagger UI development workflow
* [ ] Swagger UI documentation

### API Examples

* [ ] API examples
* [ ] Gallery API examples
* [ ] Filtering examples
* [ ] Sorting examples
* [ ] Pagination examples
* [ ] Relationship examples
* [ ] Error response examples

### Public SDK

* [ ] Public API SDK
* [ ] PHP SDK
* [ ] JavaScript / TypeScript SDK
* [ ] SDK authentication
* [ ] SDK documentation

---

## v0.7.0 - Administration Platform

### Extension Manager

* [x] Extension registration
* [x] Extension discovery
* [x] Extension state management
* [x] Enable extension
* [x] Disable extension
* [x] Install extension (zip upload, validated, audited)
* [x] Uninstall extension (files only; no automatic data rollback)
* [x] Update extension (zip upload, validated, audited, backup + rollback on failure)
* [x] Extension details page
* [x] Extension configuration page
* [ ] Extension version management (semver comparison, "update available" detection)
* [ ] Dependency visualization (interactive graph of extension dependencies)
* [ ] Extension-defined navigation tabs (an extension declares its own admin sections)
* [ ] Favourite tabs/sections per administrator
* [ ] Permission-aware UI (hide actions the current user lacks permission for)

#### Extension Manager UI (reference: Mediboard modules screen, reviewed 2026-08)

* [ ] Two tabs: "Installed (N)" / "Not installed (N)"
* [ ] Table columns: name, type, action (uninstall link), dependencies, config button, version, Active toggle, Visible toggle, dependency list
* [ ] "Activer la suppression" safety toggle: uninstall links/buttons are disabled by default and must be explicitly unlocked first, per session — a second safety layer beyond the confirm dialog
* [ ] Bulk "Update all modules (N)" action, listing how many extensions have an update available
* [ ] Per-extension "Configurer" button opens the extension configuration page/modal

### User Management

* [ ] User listing
* [ ] User creation
* [ ] User editing
* [ ] User deletion
* [ ] User profile
* [ ] Role management
* [ ] Permission management

### Settings

* [ ] Administration settings
* [ ] Extension configuration UI
* [ ] Platform configuration UI
* [ ] User preferences UI
* [ ] Language selection
* [ ] Theme selection

### Administration Infrastructure

* [ ] Reusable administration data tables
* [x] Reusable CRUD forms (modal-based, applied to Users/Roles/Gallery)
* [ ] Form validation system (client-side rules in place per form; no shared reusable rule set yet)
* [x] Notification system (toast: info, success, warning, error)
* [x] Confirmation dialogs (destructive action confirmation)
* [ ] Error handling
* [x] API loading states
* [ ] API error states
* [ ] Permission-aware UI
* [ ] Global settings/configuration search (find any setting across platform + extensions)
* [ ] Translation management interface (view/edit every translation key, platform and extensions, in one place)

#### Translation Management UI (reference: Mediboard translation screen, reviewed 2026-08)

* [ ] Filter by module (Core, or a specific installed extension), target language, and reference language
* [ ] Per-category completion percentage (e.g. "70.69% — 5297/7493 terms"), with a visible progress indicator
* [ ] Inline-editable translation rows: key on the left, editable text field with the current translation on the right
* [ ] Warning indicator on a row with a suspect/incomplete translation
* [ ] Save per category/section rather than only a single global save
* [ ] Applies uniformly to Core strings and to any installed extension's own translation files

# Platform Build & Maintenance

The platform should provide a visual build and maintenance interface allowing administrators or developers to monitor and execute common application maintenance operations.

### Build System

* [ ] Visual build interface
* [ ] Display current application version
* [ ] Display current environment
* [ ] Display build status
* [ ] Display build progress
* [ ] Display build steps
* [ ] Display build output / logs
* [ ] Display build errors
* [ ] Display build duration
* [ ] Display build completion status

### Composer Dependency Management

* [ ] Display installed Composer packages
* [ ] Display current package versions
* [ ] Detect available package updates
* [ ] Display packages requiring updates
* [ ] Display package version changes before update
* [ ] Execute Composer dependency update
* [ ] Execute targeted package updates
* [ ] Display Composer update progress
* [ ] Display Composer output / logs
* [ ] Detect Composer update errors
* [ ] Verify application after dependency updates

### Cache Management

* [ ] Visual cache management interface
* [ ] Clear application cache
* [ ] Clear configuration cache
* [ ] Clear route cache
* [ ] Clear view cache
* [ ] Clear compiled framework cache
* [ ] Provide a single "Clear All Caches" operation
* [ ] Display cache operation progress
* [ ] Display cache operation result
* [ ] Display cache operation errors

### Application Build Pipeline

* [ ] Define a central application build pipeline
* [ ] Execute build steps sequentially
* [ ] Display each build step status
* [ ] Display pending steps
* [ ] Display running step
* [ ] Display completed steps
* [ ] Display failed steps
* [ ] Allow build cancellation where technically possible
* [ ] Preserve build logs
* [ ] Provide build history
* [ ] Display build start and completion timestamps

### Maintenance Operations

* [ ] Application maintenance mode
* [ ] Enable maintenance mode before destructive operations
* [ ] Disable maintenance mode after successful operations
* [ ] Automatic recovery after failed operations
* [ ] Display maintenance operation history
* [ ] Permission-aware maintenance actions
* [ ] Confirmation dialogs for destructive operations

### Platform Health Dashboard (reference: Symfony-style Health Dashboard, reviewed 2026-08)

* [ ] Overview tab: PHP (version, server count, timezone, architecture, SAPI), Database (engine, version, storage engine, uptime), Cache (APCu/Redis/OPcache hit rate and memory usage side by side), Framework (Laravel version, debug mode, environment, maintenance/EOL dates if tracked)
* [ ] "Last hour" live panel: response time trend, request count, error rate, slow query count, failed authentication count — each with a small sparkline
* [ ] Alerts tab: surfaced platform-level warnings (e.g. approaching EOL, cache memory near limit, elevated error rate)
* [ ] Log cross-referencing tab ("Croisement des journaux"): correlate entries across log sources for a given time window
* [ ] General server info tab

### Developer / Administration UI

* [ ] Build dashboard
* [ ] Maintenance dashboard
* [ ] Composer package management page
* [ ] Cache management page
* [ ] Build history page
* [ ] Build logs viewer
* [ ] Real-time build progress
* [ ] Notifications for completed operations
* [ ] Error reporting interface

### Security

* [ ] Restrict build operations to authorised users
* [ ] Restrict Composer operations to authorised users
* [ ] Restrict cache operations to authorised users
* [ ] Protect maintenance operations
* [ ] Audit administrative operations
* [ ] Prevent arbitrary command execution through the UI
* [ ] Validate and whitelist executable maintenance operations

### Long-Term Goal

The administration interface should provide a visual equivalent of the application's maintenance and build workflow.

For example:

```text
Application Build
──────────────────────────────────────────────

✓ Checking environment
✓ Installing Composer dependencies
✓ Clearing application cache
● Building application
○ Running tests
○ Generating OpenAPI documentation
○ Building frontend assets

Progress: ███████████████░░░░░░░░ 62%

Current operation:
Building application...

[ View logs ]

──────────────────────────────────────────────
```

The system should make long-running maintenance and build operations observable without requiring administrators to execute CLI commands manually.

---

## v0.8.0 - Developer Sample Extension

The Sample Extension is a complete reference implementation designed to demonstrate how developers can build a Pixely extension.

The initial sample is a **Cinema / Movie Catalogue**.

### Sample Extension Foundation

* [ ] Sample extension manifest
* [ ] Sample extension registration
* [ ] Sample extension service provider
* [ ] Sample extension configuration
* [ ] Sample extension migrations
* [ ] Sample extension models
* [ ] Sample extension controllers
* [ ] Sample extension API
* [ ] Sample extension administration routes
* [ ] Sample extension Vue.js integration

### Movie Catalogue

* [ ] Movie model
* [ ] Movie title
* [ ] Movie description
* [ ] Movie release date
* [ ] Movie duration
* [ ] Movie language
* [ ] Movie age rating
* [ ] Movie poster
* [ ] Movie trailer
* [ ] Movie status
* [ ] Movie creation
* [ ] Movie editing
* [ ] Movie deletion
* [ ] Movie listing
* [ ] Movie detail

### Directors

* [ ] Director model
* [ ] Director creation
* [ ] Director editing
* [ ] Director deletion
* [ ] Director assignment to movies
* [ ] Director administration interface

### Actors

* [ ] Actor model
* [ ] Actor creation
* [ ] Actor editing
* [ ] Actor deletion
* [ ] Actor assignment to movies
* [ ] Actor administration interface
* [ ] Character / role information

### Trailers & Media

* [ ] Trailer model
* [ ] Trailer URL
* [ ] Multiple trailers
* [ ] Trailer administration
* [ ] Video preview
* [ ] Poster management
* [ ] Media relationships

### Favourites

* [ ] Favourite model
* [ ] Add movie to favourites
* [ ] Remove movie from favourites
* [ ] Favourite listing
* [ ] Favourite API
* [ ] Favourite administration interface

### Sample Extension API

* [ ] Movie listing endpoint
* [ ] Movie detail endpoint
* [ ] Movie creation endpoint
* [ ] Movie update endpoint
* [ ] Movie deletion endpoint
* [ ] Director endpoints
* [ ] Actor endpoints
* [ ] Favourite endpoints
* [ ] Trailer endpoints
* [ ] API filtering
* [ ] API sorting
* [ ] API pagination
* [ ] OpenAPI documentation

### Sample Extension Administration

The Sample Extension is intentionally implemented primarily as an administration example.

* [ ] Movie administration dashboard
* [ ] Movie listing
* [ ] Movie creation form
* [ ] Movie editing form
* [ ] Movie deletion
* [ ] Movie detail view
* [ ] Director management
* [ ] Actor management
* [ ] Trailer management
* [ ] Favourite management
* [ ] Poster upload
* [ ] Image preview
* [ ] Video preview
* [ ] Form validation
* [ ] API integration
* [ ] Loading states
* [ ] Empty states
* [ ] Error states
* [ ] Permission-aware actions

### Developer Examples

* [ ] Complete extension example
* [ ] Backend / frontend interaction example
* [ ] API consumption example
* [ ] CRUD example
* [ ] Relationships example
* [ ] File upload example
* [ ] Media management example
* [ ] Form validation example
* [ ] Filtering example
* [ ] Sorting example
* [ ] Pagination example
* [ ] Permissions example
* [ ] Configuration example
* [ ] Vue.js component example
* [ ] Storybook component example
* [ ] Design System usage example

---

## v1.0.0 - Gallery Extension

The Gallery Extension is the first complete Pixely Platform extension and the first major functional extension.

### Gallery Foundation

* [x] Gallery extension
* [x] Gallery manifest
* [x] Gallery service provider
* [x] Gallery web routes
* [x] Gallery API routes
* [x] Gallery photo model
* [x] Gallery database structure

### Gallery Web

* [x] Gallery web route
* [ ] Gallery listing page
* [ ] Photo detail page
* [x] Photo upload interface
* [ ] Photo editing interface
* [ ] Photo deletion interface
* [ ] Gallery administration interface
* [ ] Gallery administration dashboard

### Gallery API

* [x] `GET /api/v1/gallery`
* [x] `GET /api/v1/gallery/{photo}`
* [x] `POST /api/v1/gallery/upload`
* [x] `PUT /api/v1/gallery/{photo}`
* [x] `DELETE /api/v1/gallery/{photo}`
* [x] Gallery pagination
* [x] Gallery filtering
* [x] Gallery sorting

### Gallery Upload

* [x] Image validation
* [x] Image storage
* [x] Photo database persistence
* [x] Stored file verification
* [x] Stored file deletion
* [x] Upload API tests
* [ ] Image resizing
* [ ] Thumbnail generation
* [ ] Image optimization

### Albums

* [ ] Album model
* [ ] Album creation
* [ ] Album editing
* [ ] Album deletion
* [ ] Album / photo relationship
* [ ] Album API
* [ ] Album interface
* [ ] Album administration

### Photos

* [x] Photo model
* [x] Photo creation
* [x] Photo retrieval
* [x] Photo update
* [x] Photo deletion
* [ ] Photo metadata
* [ ] Photo visibility
* [ ] Photo ordering

### Comments

* [ ] Comment model
* [ ] Comment creation
* [ ] Comment editing
* [ ] Comment deletion
* [ ] Comment moderation
* [ ] Comment API
* [ ] Comment administration

### Tags

* [ ] Tag model
* [ ] Photo tagging
* [ ] Tag management
* [ ] Tag API
* [ ] Tag administration

### Search

* [ ] Photo search
* [ ] Album search
* [ ] Tag search
* [ ] Search API
* [ ] Search filters
* [ ] Administration search interface

### EXIF

* [ ] EXIF extraction
* [ ] EXIF storage
* [ ] Camera information
* [ ] GPS metadata
* [ ] EXIF privacy controls
* [ ] EXIF administration

---

# v1.1.0 - Platform Tooling & Developer Experience

This milestone gathers operational and developer-facing tools that support running, debugging, and extending Pixely once the core administration platform is stable. Each sub-area is independent and can be scheduled as its own sprint.

## System Observability

* [x] System log viewer (Laravel log files, filterable by level/date)
* [x] Redis cache browser (keys, values, TTL, manual eviction)
* [x] Read-only database browser (tables, columns, row preview)
* [x] Ad-hoc raw SQL query tool (read-only, permission-gated, validated single-SELECT)
* [ ] Object relationship viewer (visualize a model's internal + cross-extension relationships)

### Database Explorer (dedicated admin-only extension)

A dedicated extension, admin-only, complementing the raw SQL tool with a guided, visual, step-based interface — no SQL knowledge required to inspect or query data safely. Reference: a Mediboard-style query builder (screenshots reviewed 2026-08).

#### Query Builder — step wizard (`v-stepper`, 6 steps)

* [ ] Step 1 — Choix des classes: pick a main class/table, then add joined classes/tables via their relationships (tabbed view per joined class)
* [ ] Step 2 — Colonnes à afficher: pick displayed fields per class (tabbed field picker with search), drag-and-drop reordering of selected columns, per-column display label editable inline
* [ ] Step 3 — Contraintes: WHERE clause builder, AND/OR groups, "Ajouter une contrainte" opens a field-picker modal (tabbed by class) → then an operator select per field (`=`, `!=`, `<`, `<=`, `>`, `>=`, `like`, `between`, `in`, `is null`, `is not null`) with the matching value input(s)
* [ ] Step 4 — Options supplémentaires: two panels, "Groupement" (GROUP BY builder) and "Ordonnancement" (ORDER BY builder, numbered priority when multiple columns)
* [ ] Step 5 — Contraintes post-groupement: HAVING clause builder, same constraint UI as step 3, applied after GROUP BY
* [ ] Step 6 — Validation: read-only generated SQL preview (syntax highlighted), "Exécuter la requête" button, and a query-plan explanation table (selection type, table, access type, possible keys, chosen key, key length, reference, row count, extra info) — i.e. a friendly `EXPLAIN` rendering
* [ ] Live visual/drag-and-drop preview of the constructed query at every step, not just the final one

#### Query execution

* [ ] Configurable maximum execution time per query (admin-wide setting)
* [ ] Optional override to allow unlimited execution time (explicit opt-in, off by default)
* [ ] Execute and display results in a table
* [ ] Query timeout enforcement server-side, not just a UI setting

#### Saved query library

* [ ] Query list page: search by name, filter by last-execution date range, toggle "Toutes" / "Mes requêtes"
* [ ] "Nouvelle requête" (start the step wizard) and "Importer une requête" (import a previously exported query definition)
* [ ] Each saved query shows: name, owner (with a distinguishing colour/avatar), version count, "Aperçu" (preview), download, and history actions
* [ ] Query versioning: multiple versions per query, one marked "Active"; version history browsable
* [ ] Query locking: a query can be explicitly locked ("Verrouiller la requête") to prevent further edits
* [ ] Per-query statistics (execution count/history, expandable)

#### Query properties / export configuration

* [ ] CSV export format settings per query: field separator, field delimiter, encoding (e.g. UTF-8, ISO-8859-1)
* [ ] Toggle: show column names on the first line
* [ ] Toggle: translate result fields on execution/export (i.e. apply platform localization to displayed values, not just labels)
* [ ] Per-query access token(s), for triggering/exporting a saved query without interactive login (scoped, revocable)
* [ ] "Export de vue" — export the query as a reusable database view definition

#### Permissions

* [ ] New permissions following the established convention: `database-explorer.queries.view`, `database-explorer.queries.manage`, `database-explorer.queries.delete`, `database-explorer.queries.execute` (separate from `manage`, since running a query and being able to edit/save one are different risk levels)
* [ ] Query access tokens carry their own scope, independent of the creating user's session permissions

## Scheduled Tasks & Data Export

* [ ] Scheduled task registry (cron-style, visible in administration)
* [ ] Scheduled data export jobs (recurring exports to file/storage)
* [ ] Task execution history and failure alerts

## Data Import / Export

* [ ] CSV import
* [ ] CSV export
* [ ] XML import
* [ ] XML export
* [ ] SQL dump import
* [ ] SQL dump export
* [ ] Import validation and dry-run preview
* [ ] Export/import audit trail (who exported/imported what, when)

## PDF Generation

* [ ] PDF generation service (Core, reusable by any extension)
* [ ] Print-friendly data views (e.g. print a Gallery album, a user list)
* [ ] Fillable/editable PDF content
* [ ] PDF template system (extension-defined templates)

## Form Builder

* [ ] Dynamic form builder (field types, choices, validation rules)
* [ ] Form-to-report generation (submitted data rendered as a report)
* [ ] Form data retrieval into a document (PDF/CSV export of submissions)

## Messaging

* [ ] Messaging Core abstraction (source-agnostic)
* [ ] POP3/IMAP mailbox integration
* [ ] Message broker integration (Redis Pub/Sub)
* [ ] Message broker integration (RabbitMQ)
* [ ] Message broker integration (Kafka)
* [ ] Messaging configuration UI (choose/configure the active source)

## Audit & History

* [ ] Data change history (what changed, old/new value, per record)
* [ ] User activity journal (creations, deletions, updates, filterable by user/date/object)
* [ ] Audit log administration UI

## Design References

Reviewed as visual/UX references for the future Pixely Design System (not to be adopted wholesale — used for inspiration on layout, navigation, and component patterns):

* Vuestic Admin
* AdminLTE-based free Vue templates (dashboardpack.com demos)
* Colorlib Vuetify admin templates (colorlib.com/wp/vuetify-templates)

---

# Future Extensions

Pixely Platform is designed to support multiple independent extensions.

## Blog Extension

* [ ] Articles
* [ ] Categories
* [ ] Tags
* [ ] Comments
* [ ] Publishing workflow
* [ ] Blog API
* [ ] Blog administration
* [ ] Blog frontend

## Shop Extension

* [ ] Products
* [ ] Categories
* [ ] Cart
* [ ] Orders
* [ ] Payments
* [ ] Shop API
* [ ] Shop administration
* [ ] Shop frontend

## Media Extension

* [ ] Media library
* [ ] File management
* [ ] Storage abstraction
* [ ] Image processing
* [ ] File metadata
* [ ] Media administration

## Music Extension

* [ ] Track/album/artist library
* [ ] Audio playback
* [ ] Playlists
* [ ] Search
* [ ] Music API
* [ ] Music administration
* [ ] Music frontend player

## Media Conversion Extension

* [ ] Video → MP3 conversion
* [ ] Video → MP4 (re-encode/transcode)
* [ ] Document → PDF conversion (Word, LibreOffice formats)
* [ ] PDF → editable document conversion
* [ ] Conversion job queue and status tracking
* [ ] Conversion API
* [ ] Conversion administration

## Transport Extension

* [ ] Ride request (taxi / Uber-style / VTC)
* [ ] Map display with live itinerary
* [ ] Distance (km) and duration estimation
* [ ] Toll estimation
* [ ] Transport provider integration(s)
* [ ] Transport API
* [ ] Transport administration

## Files Extension

A reusable file-handling extension, meant to be a dependency of other extensions (Gallery, future Shop, etc.) rather than each one reimplementing upload rules independently.

* [x] Configurable max upload size (global default + per-consuming-extension override)
* [x] Allowed file type whitelist (by extension and/or MIME type — e.g. png, jpg, zip, doc)
* [x] Configurable max number of files per upload batch
* [x] Thumbnail generation
* [ ] Image resize (on upload, and on-demand by requested dimensions)
* [x] Shared storage/validation service consumed by other extensions via a declared dependency (e.g. Gallery `requires: ['files']`)
* [ ] Files API (upload, list, delete) usable standalone or embedded
* [ ] Files administration screen (view stored files, usage per consuming extension)

### Planned consumers

* [x] Gallery Extension: photo upload delegates validation/thumbnailing to Files Extension instead of its own ad-hoc logic
* [ ] Shop Extension (future): product images use Files Extension the same way

## Translations Extension

Implements the Translation Management UI already specified under Administration Infrastructure (v0.7.0) as an actual installable extension, not a hardcoded Core feature — consistent with ADR-0001 ("Pixely is a Platform, not an Application").

* [ ] Same feature set as already detailed in "Translation Management UI" (module/language filter, completion percentage, inline editing, per-category save)
* [ ] Applies uniformly to Core strings and any installed extension's own translation files, discovered dynamically rather than hardcoded per module

---

# Long-Term Platform Goals

### Extension Ecosystem

* [ ] Extension marketplace
* [ ] Extension installation
* [ ] Extension updates
* [ ] Extension dependency resolution
* [ ] Extension compatibility checks
* [ ] Extension security validation
* [ ] Extension ratings
* [ ] Extension documentation
* [ ] Extension developer portal

### Platform

* [ ] Multi-language platform
* [ ] Multi-site support
* [ ] Configuration management
* [ ] Event system
* [ ] Job / queue system
* [ ] Notification system
* [ ] Caching
* [ ] Logging and monitoring
* [ ] Audit system
* [ ] Backup system

### Frontend Platform

* [ ] Vue.js 3 platform architecture
* [ ] TypeScript architecture
* [ ] Vuetify integration
* [ ] Pixely Design System
* [ ] Storybook component library
* [ ] Material Design guidelines
* [ ] Theme system
* [ ] Dark mode
* [ ] Accessibility standards
* [ ] Responsive administration
* [ ] Reusable administration components

### Developer Platform

* [ ] Complete Extension SDK
* [ ] Extension generator
* [ ] Developer documentation
* [ ] API documentation
* [ ] Swagger UI
* [ ] OpenAPI generation
* [ ] CLI tooling
* [ ] Extension testing framework
* [ ] Frontend extension tooling
* [ ] Storybook extension tooling
* [ ] Sample extensions
* [ ] Developer tutorials
* [ ] Extension development cookbook

### Quality

* [ ] Unit test coverage
* [ ] Feature test coverage
* [ ] API test coverage
* [ ] Frontend test coverage
* [ ] Component test coverage
* [ ] Storybook component testing
* [ ] Static analysis
* [ ] Code style enforcement
* [ ] Security analysis
* [ ] Performance testing
* [ ] Accessibility testing
* [ ] CI/CD pipeline

---

# Current Execution Order

CURRENT
 │
 ▼
Extension-declared permissions (dynamic, replaces hardcoded seeder)
 │
 ▼
Incremental extension upgrade mechanism (versioned steps, not full zip replace)
 │
 ▼
Files Extension (upload rules, thumbnails, resize) — dependency for Gallery
 │
 ▼
Translations Extension
 │
 ▼
Extension Manager remaining items (version management, dependency graph, nav tabs, favourites, permission-aware UI)
 │
 ▼
Users: profile, avatar (via Files Extension), preferences
 │
 ▼
Extension settings screen
 │
 ▼
Sample Cinema Extension + frontend training
 │
 ▼
Gallery Administration (visual, albums, tags, search, EXIF)

# Current Progress

The Pixely Platform currently has a functional extension foundation with:

* Extension contracts and manifests
* Extension discovery and registry
* Extension lifecycle management
* Dependency management
* Extension state persistence
* Extension configuration and persistence
* Extension commands
* Gallery extension
* Gallery photo model
* Gallery web routes
* Gallery API
* Gallery CRUD operations
* Image upload and storage
* Automatic stored-file deletion
* API query parsing
* API query filtering
* API query sorting
* API pagination
* API relationships
* Automated tests

The current API query layer is stable and its Gallery API tests are green.

The next development focus is:

1. Consolidate the Platform API documentation architecture.
2. Replace manually maintained OpenAPI documentation with automatic generation from PHP.
3. Introduce `zircote/swagger-php` and PHP OpenAPI attributes.
4. Generate `openapi.yml` from the application source code.
5. Integrate Swagger UI with the generated specification.
6. Complete the API documentation workflow.
7. Continue the Extension SDK.
8. Administration foundation started: Vue.js 3, TypeScript, Vue Router, AdminLayout, DashboardView and navigation are implemented and verified on `/admin`.
9. Build reusable UI components (buttons, tables, cards, forms) before introducing Vuetify and the Pixely Material Design System.
10. Introduce the API client architecture to connect the administration to the Platform API.
11. Introduce Storybook for reusable Vue.js components.
12. Create administration mockups using Figma or an equivalent free design tool.
13. Build the Sample Cinema Extension as a developer reference.
14. Continue the Gallery Extension with its visual administration interface.

The development process should continue through clearly defined sprints, with each sprint having:

* A defined objective
* A limited scope
* Step-by-step implementation tasks
* Automated tests
* Documentation updates
* A final validation
* A Git commit at the end of the sprint

The roadmap should be updated progressively as each sprint is completed.
