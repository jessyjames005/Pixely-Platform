# Website Engine Extension Contracts

Extensions expose public content through the Website Engine without direct coupling to its internals. This document is the authoritative reference for how an extension publishes public pages, assets, routes, and metadata. The CinemaMovie extension is the reference implementation.

## Design principles

- **Server-side authoritative.** Permissions, validation, and publication state are enforced by Laravel, never by the Vue client. The client is a consumer of the contract, not a source of truth.
- **No domain leakage.** The Website Engine never imports extension models. Extensions integrate through explicit contracts (page providers, block allowlists, namespaced routes).
- **Read-only public surface.** Public endpoints expose published content only. Every mutation (create, update, delete, rate, favorite) stays behind authentication and permission middleware.

## Capabilities

An extension declares what it contributes to the public website:

- `pages`: extension-declared public page definitions (title, slug, locale, blocks, SEO).
- `blocks`: namespaced block types with a schema, rendered from an allowlist.
- `navigation`: menu contributions.
- `seo`: metadata providers (title, description).

## Route conventions

### Public website routes

- `GET /` — website entry point. Currently redirects to the CinemaMovie experience at `/pixely-movies`.
- `GET /pixely-movies/{any?}` — Pixely Movies single-page application (home, movie detail, people). Served by `routes/web.php` via `view('app')` so direct loads and deep links resolve without a published-page record.
- `Route::fallback(PublicPageController::class)` — serves the Vue application only for **published** website pages. Unknown or unpublished paths, and reserved prefixes (`admin`, `account`, `api`, `docs`, `login`, `sanctum`, `up`), return a real `404`.

### Public data proxy (read-only)

Extension public data is exposed under `/api/v1/public/...`. CinemaMovie proxies TMDB v3 under `/api/v1/public/tmdb/...`:

- `GET /api/v1/public/trending-movies`
- `GET /api/v1/public/tmdb/popular-movies`
- `GET /api/v1/public/tmdb/top-rated-movies`
- `GET /api/v1/public/tmdb/upcoming-movies`
- `GET /api/v1/public/tmdb/now-playing-movies`
- `GET /api/v1/public/tmdb/popular-people`
- `GET /api/v1/public/tmdb/person/{id}`
- `GET /api/v1/public/tmdb/person/{id}/movie-credits`
- `GET /api/v1/public/tmdb/movie/{id}`
- `GET /api/v1/public/tmdb/movie/{id}/credits`
- `GET /api/v1/public/tmdb/search`

Rules that apply to every public proxy route:

- **Read-only.** Only `GET` is registered; `POST`/`PUT`/`DELETE` return `405`.
- **Numeric identifiers are constrained at the route level** (`->whereNumber('id')`) so invalid input returns `404` instead of reaching the controller.
- **Rate limited** (`throttle:120,1`) so the server-side upstream API key cannot be abused.
- **Input validated server-side**: `page` must be an integer in `1..500`; `search.query` is required, a string of `1..200` characters. Validation failures return a `422` JSON:API error envelope.

### Authenticated administration API

Mutations live under `/api/v1/cinema-movies`, `/api/v1/cinema-genres`, etc., protected by `auth:sanctum` plus per-action permission middleware (`cinema-movie.movies.view`, `cinema-movie.movies.manage`, `cinema-movie.movies.delete`). The public frontend never calls these without an authenticated, authorized session.

## Rendering

- Public rendering is performed by the Vue single-page application (`PublicPageView.vue`) mounted on the `public` surface.
- `PublicPageView` distinguishes between **website pages** (rendered from the Website Engine page model) and **extension views** (CinemaMovie home, movie detail, people). Extension views fetch their own data from the public proxy and do not depend on a published-page record.
- Website pages render only **allowlisted block types**: `heading`, `paragraph`, `cta`. Unknown block types are ignored rather than executed.
- Extension-provided templates (for example the CinemaMovie movie detail) are dedicated Vue components selected by the page `template` field or route prefix.

## Asset loading

- Frontend assets are built with Vite (`npm run build`) into `public/build/` and referenced through the Laravel manifest.
- Extension Vue components are bundled into the single SPA bundle; there is no per-extension runtime asset loader yet.
- Upstream media (TMDB posters/backdrops) are loaded directly from `https://image.tmdb.org` by the client using the path returned by the API; the platform does not proxy binary images.

## Error handling

- **Unknown/unpublished pages** return a real `404` (server-side, via `PublicPageController`).
- **Invalid proxy input** returns `422` with the platform JSON:API error envelope (`errors[].code`, `errors[].title`, `errors[].detail`, `errors[].source.pointer`).
- **Upstream failures** are logged server-side and returned as a JSON `error` payload; the client renders an explicit empty/error state rather than sample data.
- **Per-block isolation**: a failing block does not abort the whole page render; unknown block types are skipped.

## Route compatibility

- Extension public routes are **namespaced** under a stable prefix (`/pixely-movies`, `/api/v1/public/tmdb/...`) so they never collide with core or other extensions.
- JSON:API resources are **versioned** under `/api/v1`.
- Route identifiers and prefixes are part of the contract; renaming them is a breaking change and must be reflected here and in the changelog.

## Reference implementation: CinemaMovie

- Public experience: `/pixely-movies` (home, `movie/{tmdbId}`, `people`, `person/{id}`).
- Public data: `/api/v1/public/tmdb/...` (read-only, throttled, validated).
- Administration: `/api/v1/cinema-movies` (auth + permission).
- Validation tests: `PublicMovieControllerTest` (404 on non-numeric id, 422 on page/query violations, 405 on non-GET, throttling middleware registered).
- Known limitation: no frontend E2E/Playwright coverage for the public cinema journeys yet; block-level error isolation is partial (unknown types are skipped, not individually recovered).