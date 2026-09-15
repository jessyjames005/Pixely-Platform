# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog.
Versioning follows Semantic Versioning.

## [Unreleased]

### Added
- Initial project structure
- Project vision
- Roadmap
- Architecture Decision Records

## [0.1.0] - In Progress

### Added
- Repository initialization
- Documentation foundation

### Added

- Added Gallery API endpoints for listing, viewing, creating, updating and deleting photos.
- Added Gallery image upload through the API.
- Added Gallery upload validation.
- Added public storage handling for uploaded gallery images.
- Added automatic deletion of the stored image when a photo is deleted.
- Added feature tests covering the Gallery API.

### Added

- Added pagination support to the Gallery API.
- Added configurable `page` and `per_page` query parameters.
- Limited Gallery API page size to a maximum of 100 photos.
- Added pagination metadata to Gallery API responses.
- Added automated tests for Gallery API pagination.
- Documented Gallery API pagination in OpenAPI.

### Added

- Added the Tuleap extension: a sprint management dashboard proxying a Tuleap instance (projects, milestones, sprint stats, burndown, sprint history) alongside locally-owned team/CAF/retrospective data.
- Added 7 admin views for the Tuleap extension: Dashboard, Sprint Planning, Sprint Review, Retrospective, Sprint Analytics (Tendances), Team Settings, System Settings.
- Added shared Tuleap components: StatCard, AvatarStack, AlertsPanel, BurndownChart (hand-rolled SVG), ProjectSprintSelector.

### Fixed

- Fixed `TuleapService` not implementing `TuleapServiceInterface` (the class was incomplete and did not even close, causing a fatal error on every request touching the extension). Ported the missing Tuleap API proxy and business logic (sprint stats/alerts, burndown, sprint history with predictability/capacity) from the original Node.js prototype.
- Fixed Tuleap error responses not matching the platform's standard `{error:{code,message}}` envelope, via new `TuleapUnavailableException` / `TuleapApiException` classes.
- Fixed the Tuleap extension's frontend not being wired into the application at all (no navigation entry, no routes, store using axios instead of the shared `apiClient`).

### Added

- Added user preferences: theme (system/light/dark), interface density (default/comfortable/compact), and an email notifications opt-out flag, stored on the existing per-user settings row.
- Added a "Préférences" section to the My Profile self-service screen.
- Added platform-wide application of the theme/density preference via Vuetify's `useTheme()` and `<v-defaults-provider>`.
- Added automated tests for the new preference fields, including backfilling them onto settings rows saved before this change.

### Fixed

- Fixed `RolesView.vue` being entirely missing from disk while the router already imported it, breaking the admin build for anyone navigating to Roles & Permissions → Roles.

### Added

- Added a card-based Roles list (Material 3 / Vuetify), each card showing the role's user count, avatar stack, and Edit/Duplicate/Delete actions, plus an "Add New Role" card.
- Added an Edit Role modal with permissions grouped by domain (Core sub-domains vs. each extension, derived from each permission's `<domain>.<object>.<action>` name), one row per object with whichever action checkboxes actually exist for it, and a per-group "Select All".
- Added a "Total users with their roles" table below the card grid (user, role, active/inactive status), built from the roles already loaded rather than a new endpoint.
- Added an adaptive "Accessibilité" control per permission row in the Edit Role modal: a three-state Interdit/Lecture seule/Lecture et écriture toggle where an object has a `view` action plus another action, otherwise a plain Interdit/Autorisé toggle.
- Added a read-only "Droits existants" matrix (role × object access level) below the roles list, collapsible, built from data already loaded.

### Changed

- Removed the unused `ADMIN_DEFAULT_PERMISSIONS` constant from `RolePermissionSeeder` (dead code — `run()` already used `PERMISSIONS` directly).

### Added

- Added Enabled/Disabled tabs to the Extension Manager screen (adapted from the Mediboard reference's Installed/Not installed tabs — Pixely has no catalog of known-but-absent extensions, so that distinction doesn't apply here; every discovered extension is already "installed" by definition).

### Changed

- Removed the "ID" column from the Extension Manager table (name is the human-facing identifier; the id is still used internally).

### Added

- Added a standalone Files API (`GET/POST /files`, `GET/DELETE /files/{file}`) with its own `files` table tracking uploads made through it, independent of Gallery's and the profile avatar's own separate storage.
- Added a Files administration screen (`/admin/files`): grid view with image thumbnails or a type icon otherwise, upload, delete, pagination.
- Added `files.view`/`files.manage`/`files.delete` permissions, declared via `ExtensionPermissionsInterface` and granted to the admin role.
- Added Pest coverage for the Files API (auth required, upload validation, list pagination, show, delete).

