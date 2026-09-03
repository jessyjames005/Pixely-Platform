# Pixely Platform — Project Rules Skill

## Purpose

This document defines the rules that must be respected throughout the Pixely Platform project. It is the project-level reference for architecture, API design, frontend development, documentation, testing, and developer experience.

The goal is to keep Pixely Platform modular, maintainable, extensible, documented, and consistent as new extensions are added.

---

## 1. Core architectural principle

### Pixely is a Platform, not an Application

Pixely Platform must remain a generic platform capable of hosting independent extensions.

Do not design the core around a single business domain such as the Gallery extension.

### Rules

- Core functionality belongs in `App/Core` only when it is genuinely reusable by multiple extensions.
- Business-specific functionality belongs inside its extension.
- Extensions must avoid unnecessary coupling to other extensions.
- The Gallery extension is an extension, not the definition of the platform itself.
- New functionality must first be evaluated as either:
  - Core functionality;
  - Extension functionality;
  - Shared infrastructure.
- Do not move extension-specific concepts into Core merely for convenience.

---

## 2. Extension architecture

Every extension should be independently understandable and maintainable.

Recommended structure:

```text
app/
└── Extensions/
    └── ExtensionName/
        ├── Http/
        ├── Models/
        ├── Services/
        ├── Providers/
        ├── Database/
        ├── Resources/
        └── ...
```

### Rules

- Extensions own their domain logic.
- Extension routes remain inside the extension.
- Extension migrations remain associated with the extension.
- Extension tests should be grouped by extension.
- Extension APIs must use the shared Core API infrastructure.
- Avoid direct access to another extension's internal implementation.

---

## 3. API architecture

Pixely uses a versioned HTTP API.

Current API convention:

```text
/api/v1/...
```

### API query system

The shared API query system is responsible for:

- filtering;
- sorting;
- pagination;
- relationship inclusion;
- future reusable query capabilities.

The following classes form part of this infrastructure:

```text
App/Core/Api/Query/
├── ApiQuery.php
├── ApiQueryParser.php
├── ApiQueryApplier.php
├── FilterExpression.php
├── FilterOperator.php
└── FilterParser.php
```

### Rules

- Do not duplicate filtering, sorting, or pagination logic inside individual controllers.
- Controllers should remain thin.
- Query parsing belongs to `ApiQueryParser` and related classes.
- Query application belongs to `ApiQueryApplier`.
- New query operators must be implemented centrally and tested centrally.
- API behaviour must be covered by automated tests.

---

## 4. OpenAPI documentation

OpenAPI is generated automatically from the PHP source code by static analysis — no hand-maintained YAML, no manual annotation of every parameter/response.

### Pipeline

```text
PHP controllers, validation rules, type hints
        ↓
dedoc/scramble (static analysis)
        ↓
docs/api/openapi.json (generated on demand)
        ↓
Swagger UI (resources/views/api/swagger.blade.php)
```

### Rules

- Do not hand-write OpenAPI YAML/JSON. If Scramble infers something incorrectly, fix it at the source (validation rules, type hints, API Resources) or use a targeted Scramble attribute (`#[BodyParameter]`, `#[QueryParameter]`, etc.) — never bypass generation with a static file.
- **Every new controller exposing API routes must declare `#[Dedoc\Scramble\Attributes\Group('Domain Name', weight: N)]` on the class.** This is mandatory, not optional: without it, Scramble falls back to grouping by controller class name, which produces an inconsistent, unordered Swagger UI. Pick the next unused `weight` value, incrementing from the highest currently in use, so new domains append after existing ones.
- Group names must match the domain, not the controller name (e.g. `'Roles & Permissions'`, not `'RoleController'`).
- A PHPDoc block on each controller method improves the generated documentation (first line = summary, remaining lines = description) — add one whenever the method name alone doesn't make the endpoint's purpose obvious.
- `docs/api/openapi.json` is a generated artifact (served live via `Scramble::registerJsonSpecificationRoute`) and must never be manually edited or committed as a static override.
- Swagger UI (`/docs/api`) is the project's single API documentation interface.

### Reference

See `docs/handbook/core/api-documentation.md` for the full Scramble setup and grouping conventions.

---

## 4bis. Permission naming convention

Permissions follow a fixed pattern: `<domain>.<object>.<action>`.

### CRUD objects

Use exactly these three action suffixes — never `create`/`update`/`edit` as separate permissions:

- `view` — read access
- `manage` — create + update (deliberately merged; if a role needs to create/edit but never delete, grant `manage` without `delete`)
- `delete` — separate and revocable independently of `manage`, since deletion is irreversible

A role with no permission at all for an object is implicitly forbidden — there is no explicit "forbidden" permission to create.

Example: `gallery.photos.view`, `gallery.photos.manage`, `gallery.photos.delete`.

### Non-CRUD tools

Platform tools that aren't CRUD objects (log viewers, cache browsers, SQL console) use explicit action names instead of forcing the view/manage/delete vocabulary:

Example: `system.logs.view`, `system.cache.clear`, `system.sql.query`.

### Rules

- Always set `guard_name: 'web'` explicitly when creating Role/Permission (see section on Spatie guard drift with Sanctum).
- New extensions must declare their own permissions using this convention; extension-owned permission registration (synced on enable) is planned but not yet implemented — see `ROADMAP.md`.

---

## 5. API design rules

### Versioning

Use:

```text
/api/v1/...
```

for the current stable API.

### Responses

API responses should use a consistent structure.

Collection responses should expose pagination metadata when pagination applies.

Errors should use a consistent machine-readable error code and human-readable message.

### Rules

- Never expose internal implementation details unnecessarily.
- Do not return database models blindly when a dedicated API representation is required.
- Use explicit HTTP status codes.
- Validate input at the API boundary.
- Document request parameters, request bodies, responses, and errors in OpenAPI.
- Keep API behaviour predictable between extensions.

---

## 6. Backend rules

### Laravel

Laravel is the backend framework.

Backend code must favour:

- clear responsibilities;
- dependency injection;
- small focused classes;
- explicit contracts;
- testability;
- reusable Core services where appropriate.

### Controllers

Controllers should orchestrate requests rather than contain large business algorithms.

A controller should generally:

1. Receive the request.
2. Validate/parse input.
3. Delegate work to the appropriate service/query layer.
4. Return the appropriate API response.

### Database

- Database access must remain explicit and testable.
- Avoid unnecessary queries.
- Pay attention to N+1 problems.
- Relationships should be loaded intentionally.
- Database-specific behaviour should not leak unnecessarily into generic Core abstractions.

---

## 7. Frontend architecture

Vue 3 is the frontend framework.

The project will use:

- Vue 3;
- TypeScript where appropriate;
- Vuetify;
- Material Design principles;
- a reusable Pixely Design System;
- Storybook.

### Rules

- Prefer reusable components over duplicated UI code.
- Do not create one-off visual components when the Design System should provide the component.
- Keep business-specific UI inside the relevant extension.
- Shared components belong in the platform frontend/design-system layer.
- API communication should be separated from presentation components.

---

## 7bis. Frontend domain structure

Every frontend domain (Core module or backend extension) is organized under `resources/js/extensions/<domain>/` with an identical internal structure, regardless of whether the domain is Core or an extension on the backend.

### Required subfolders

```text
extensions/<domain>/
├── components/    # Domain-specific components (not shared across domains)
├── composables/   # Reactive helpers specific to this domain
├── docs/           # Domain-specific developer notes, not covered elsewhere
├── entities/       # Domain entity classes/factories, if richer than a plain type
├── enums/          # Domain enums (string/int unions with meaning)
├── models/         # Domain data shapes returned by the API (replaces loose "types/api.ts" per domain)
├── store/          # Pinia store(s) for this domain
├── styles/         # .scss files; no <style> blocks inside .vue files
├── tests/          # Vitest unit tests for this domain
├── types/           # Domain-specific TypeScript types not tied to a Pinia store
├── utils/           # Pure helper functions specific to this domain
└── views/           # Route-level page components
```

### Rules

- A component, composable, or util used by **only one** domain lives in that domain's folder. If a second domain needs it, promote it to `shared/`.
- `shared/` holds only genuinely cross-domain code — generic UI (`BaseButton`, `BaseTable`, etc.), generic composables (`useApi`), and shared types (API envelopes).
- State management uses **Pinia**. Each domain defines its own store(s) under `store/`; no module-level singleton composables for state (the previous `useAuth`-style pattern is retired in favour of a Pinia store).
- Styling lives in `.scss` files under `styles/`, imported by the component — not written inline in a Vue SFC's `<style>` block. A component file contains markup and logic only.
- New domains must follow this structure from their first commit — it is not something to "clean up later".

---

## 8. Material Design and Vuetify

Pixely's administration interface will follow Material Design 3 (M3) principles and use Vuetify as the implementation layer.

### Rules

- Vuetify is the default component library for the administration UI.
- Material Design 3 is the design-system baseline for new Pixely UI/UX work.
- Avoid introducing another UI component library without an explicit architectural decision.
- Establish Pixely-specific theme tokens on top of Vuetify.
- Align Pixely theme tokens with Material 3 tokens where applicable.
- Reuse spacing, typography, colours, elevation, forms, tables, dialogs, alerts, and navigation patterns consistently.
- Accessibility must be considered for reusable components.

---

## 9. Pixely Design System

Pixely must have a reusable Design System based on Material Design 3 and Vuetify.

The Design System should progressively define:

- colours;
- typography;
- spacing;
- elevation;
- borders and radii;
- buttons;
- inputs;
- selects;
- tables;
- cards;
- dialogs;
- alerts;
- navigation;
- pagination;
- loading states;
- empty states;
- error states;
- confirmation patterns;

### Rule

New administration UI should reuse the Pixely Design System instead of inventing visual patterns independently.

---

## 10. Storybook

Storybook will be used for the Vue.js component library and Design System.

### Purpose

Storybook provides:

- isolated component development;
- visual documentation;
- component examples;
- states and variants;
- regression support;
- a reference for extension developers.

### Rules

Reusable UI components should have Storybook stories.

Stories should demonstrate important states such as:

- default;
- loading;
- empty;
- error;
- disabled;
- validation;
- responsive variations where relevant.

Storybook must remain focused on reusable UI components, not complete business workflows.

---

## 11. Figma / design workflow

Pixely may use Figma or a suitable free alternative for interface design and prototypes.

The Pixely UI/UX Kit is the authoritative project design reference. Material Design 3 is the baseline for the design system.

### Rules

- Design major administration workflows before implementing complex UI when practical.
- Designs should follow the Pixely UI/UX Kit and Material Design 3.
- Do not design components that cannot reasonably be represented by the chosen frontend architecture.
- Design decisions should be documented when they affect the reusable platform UI.
- External templates may be used as visual references, but must be adapted to the Pixely Design System.

---

## 12. Sample extension

Pixely will include a developer-oriented sample extension.

The sample extension will demonstrate realistic frontend/backend interaction inside the administration area.

### Sample domain

A cinema/movie catalogue may include:

- films;
- favourites;
- create film;
- edit film;
- directors;
- actors;
- trailers/videos;
- film language;
- minimum viewing age;
- poster/cover image;
- other useful metadata.

### Purpose

The sample extension is not intended to become the main business domain of Pixely.

It exists to demonstrate:

- extension architecture;
- database relationships;
- API endpoints;
- OpenAPI generation;
- Vue administration pages;
- Vuetify components;
- Design System usage;
- Storybook components;
- frontend/backend communication;
- validation;
- uploads/media;
- relationships;
- filtering and pagination;
- developer extension patterns.

### Rules

- Keep the sample extension isolated.
- Prefer realistic interactions over artificial demo screens.
- It should serve as documentation by example for extension developers.
- It may be implemented between other extensions when useful for demonstrating platform capabilities.

---

## 13. Administration interface

The administration interface is a major future milestone, not something to rush into before the platform foundations are stable.

The implementation should progress in this order:

```text
Core architecture
    ↓
API infrastructure
    ↓
OpenAPI generation
    ↓
Extension contracts
    ↓
Frontend foundation
    ↓
Vuetify + Material Design
    ↓
Pixely Design System
    ↓
Storybook
    ↓
Administration shell
    ↓
Extension administration screens
    ↓
Sample extension
```

This order may be adjusted when a sprint requires a small vertical slice.

---

## 14. Testing rules

Every significant backend feature must have automated tests.

### Rules

- Feature tests for API behaviour.
- Unit tests for complex Core logic.
- Regression tests when fixing a bug.
- Test both successful and invalid requests.
- Test boundary values.
- Test pagination boundaries.
- Test filter operators.
- Test sorting.
- Test relationships/includes.
- Keep tests readable and focused on behaviour.

A green test suite is a prerequisite for moving a sprint to completion.

---

## 15. Documentation rules

Project documentation is written in English.

Documentation should explain:

- what a component does;
- why the architecture exists;
- important constraints;
- extension contracts;
- API behaviour;
- developer workflows;
- architectural decisions.

### Code documentation

Code should include useful PHPDoc/comments for:

- classes with architectural responsibilities;
- non-obvious methods;
- important algorithms;
- extension contracts;
- public reusable APIs.

Do not add comments that merely repeat obvious code.

---

## 16. Git rules

Keep commits focused and understandable.

### Rules

- One logical change per commit where practical.
- Do not commit generated or temporary files unless explicitly required.
- Do not commit secrets.
- Run relevant tests before committing.
- Check `git diff` before committing.
- Keep documentation changes synchronized with architectural changes.
- Do not rewrite history unnecessarily on shared branches.

Before considering a sprint complete:

```bash
git status
git diff
docker compose exec app php artisan test
```

The exact test command may be narrowed during development, but the final relevant suite must be green.

---

## 17. Docker rules

The project development environment uses Docker Compose.

### Rules

- Development services should be reproducible through Docker.
- Prefer executing project-specific commands inside the appropriate container.
- Avoid depending on software installed only on one developer machine.
- Node-based tooling used by the project should be available through the Docker development environment where practical.
- OpenAPI generation and frontend tooling should be reproducible in Docker/CI.

---

## 18. Dependency rules

Before introducing a new dependency:

1. Check whether the functionality already exists in Laravel, Vue, Vuetify, or the project Core.
2. Check whether the dependency is actively maintained.
3. Check its licence and compatibility.
4. Check whether it creates architectural coupling.
5. Document important architectural decisions.

Important planned dependencies include:

- `dedoc/scramble` for OpenAPI generation (static analysis, no manual annotations);
- Swagger UI for API documentation;
- Vuetify for administration UI components;
- Storybook for Vue component documentation.

---

## 19. Security rules

- Never trust request input.
- Validate all external input.
- Do not expose sensitive fields accidentally through API responses.
- Do not expose stack traces in production API responses.
- Protect administration routes with the appropriate authentication/authorization mechanisms.
- Uploaded files must be validated.
- File names and paths must not be trusted directly from users.
- Relationships included through API query parameters must be explicitly controlled where necessary.

---

## 20. Performance rules

Performance must be considered without prematurely complicating the architecture.

Pay particular attention to:

- N+1 queries;
- pagination;
- large API responses;
- image handling;
- eager loading;
- unnecessary frontend requests;
- excessive component rendering.

Optimise based on evidence and tests rather than speculation.

---

## 21. Accessibility rules

Administration UI components should aim for accessible behaviour from the beginning.

Consider:

- keyboard navigation;
- semantic HTML;
- labels;
- focus states;
- readable contrast;
- accessible validation messages;
- screen-reader-friendly controls.

Reusable accessibility behaviour should be implemented at the Design System level where possible.

---

## 22. Roadmap discipline

Development must proceed sprint by sprint.

Each sprint must have:

1. A clear objective.
2. A limited scope.
3. Explicit implementation steps.
4. Tests to add or update.
5. Documentation to update.
6. A definition of done.
7. A validation step before moving to the next sprint.

Do not start several architectural features simultaneously without a clear reason.

### Important principle

A sprint can introduce a small vertical slice of a future feature, but it must not destabilise the current architecture.

---

## 23. Visual administration roadmap

The visual administration work is intentionally staged.

### Foundation phase

- Vue 3 foundation.
- TypeScript setup where appropriate.
- Vuetify integration.
- Material Design theme.
- Pixely Design System foundations.
- Storybook setup.

### Administration phase

- Administration shell.
- Navigation.
- Dashboard foundation.
- Extension navigation.
- Reusable tables.
- Forms.
- Filters.
- Pagination.
- Media upload UI.
- Notifications and error handling.

### Extension phase

- Gallery administration.
- Sample cinema/movie administration.
- Other extensions as the platform grows.

The visual phase should begin once the Core/API architecture is sufficiently stable to avoid building UI against constantly changing contracts.

---

## 24. Definition of Done

A feature is considered complete only when applicable items below are satisfied:

- [ ] Architecture follows Pixely Platform principles.
- [ ] Code is placed in the correct Core/Extension boundary.
- [ ] API behaviour is tested.
- [ ] Validation is implemented.
- [ ] OpenAPI PHP attributes are updated.
- [ ] Generated OpenAPI specification is valid.
- [ ] Swagger UI exposes the documented endpoint when applicable.
- [ ] Frontend components use the Design System when applicable.
- [ ] Storybook stories exist for new reusable components.
- [ ] Documentation is updated.
- [ ] Relevant tests are green.
- [ ] `git diff` has been reviewed.
- [ ] No unnecessary temporary/debug code remains.

---

## 25. Golden rule

When implementing a new feature, always ask:

> **Is this a platform capability, an extension capability, or a reusable UI capability?**

Then place it in the smallest appropriate boundary and expose it through a clear contract.

Pixely should become more extensible as development progresses, not more coupled.
