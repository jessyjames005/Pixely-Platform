# Authentication

## Introduction

Pixely Platform's administration is authenticated using **Laravel Sanctum in SPA mode**: session-based, cookie-driven authentication for a first-party frontend served from the same origin as the API.

There are no API tokens to store, rotate, or leak on the client. The browser's session cookie and Laravel's CSRF protection do the work.

---

# Why Sanctum SPA (not tokens)

Pixely serves the Vue administration SPA directly from Laravel (`routes/web.php` → `/admin/{any?}` and `/login`), on the same domain as the API (`/api/v1/*`). This is exactly the scenario Sanctum's SPA mode is designed for.

Alternatives considered:

* **Sanctum API tokens (Bearer)** — appropriate for third-party or mobile clients. Rejected for the admin SPA: tokens would need to be stored in the browser (localStorage/memory), reintroducing XSS exposure the session-cookie approach avoids, for no benefit since the SPA and API share an origin.
* **Plain Laravel session auth with Blade login** — would work but ignores that the frontend is a Vue SPA making `fetch` calls; Sanctum's stateful middleware and CSRF cookie endpoint solve exactly this without extra code.

See `ADR-0007-sanctum-spa-authentication.md` for the full decision record.

---

# Backend

## Registration

Authentication is Core infrastructure, not an extension — it cannot be disabled, so it does not go through the Extension Kernel/manifest mechanism used by Gallery and other extensions. It is registered directly like `AppServiceProvider` and `PixelyServiceProvider`:

```php
// bootstrap/providers.php
return [
    AppServiceProvider::class,
    PixelyServiceProvider::class,
    AuthServiceProvider::class,   // App\Core\Auth\Providers\AuthServiceProvider
    ExtensionServiceProvider::class,
];
```

`AuthServiceProvider` registers its own routes under `api/v1`, following the same per-module routing convention as extensions:

```php
$this->app->router
    ->middleware('api')
    ->prefix('api/v1')
    ->group(__DIR__ . '/../routes/api.php');
```

## Routes

```text
POST  /api/v1/auth/login    (public)
POST  /api/v1/auth/logout   (auth:sanctum)
GET   /api/v1/auth/me       (auth:sanctum)
GET   /sanctum/csrf-cookie  (provided by Sanctum)
```

`AuthController` (`App\Core\Auth\Http\Controllers\AuthController`):

* `login()` — validates credentials, calls `Auth::attempt()`, regenerates the session, and returns the authenticated user via `ApiResponse`. Returns a `401 INVALID_CREDENTIALS` error on failure.
* `logout()` — logs out the `web` guard, invalidates the session, regenerates the CSRF token, returns `204`.
* `me()` — returns the currently authenticated user, or `401` (via the `auth:sanctum` middleware) if there is none.

## Configuration

```env
SANCTUM_STATEFUL_DOMAINS=localhost:8080
SESSION_DOMAIN=localhost
```

`bootstrap/app.php` enables Sanctum's stateful API middleware:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();
})
```

## Protecting extension routes

Extensions protect their own write routes with `auth:sanctum`, following the same convention as any other route group. Example (Gallery):

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/gallery/upload', [GalleryController::class, 'store']);
    Route::put('/gallery/{photo}', [GalleryController::class, 'update']);
    Route::delete('/gallery/{photo}', [GalleryController::class, 'destroy']);
});
```

Read endpoints (`index`, `show`) remain public by default — this is a per-extension decision, not a platform-wide rule.

## Error handling

`AuthenticationException` and `AuthorizationException` are mapped to `401`/`403` in `bootstrap/app.php`'s exception renderer, keeping the same `{ error: { code, message } }` envelope used across the whole API:

```php
$status = match (true) {
    $exception instanceof ValidationException => 422,
    $exception instanceof \Illuminate\Auth\AuthenticationException => 401,
    $exception instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
    $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $exception->getStatusCode(),
    default => 500,
};
```

---

# Frontend

## Session flow

```text
LoginView
   │  fetchCsrfCookie()      → GET /sanctum/csrf-cookie
   │  login(email, password) → POST /api/v1/auth/login
   ▼
useAuth (module-level state)
   │  user = response.data
   ▼
Router guard allows /admin
```

## `apiClient.ts`

Every request sent through `apiClient`:

* includes `credentials: 'include'` so the session cookie is sent/received;
* reads the `XSRF-TOKEN` cookie and sends it back as the `X-XSRF-TOKEN` header, satisfying Laravel's CSRF check on state-changing requests.

## `useAuth.ts`

A module-level composable (singleton state shared by every component that calls it):

```ts
const { user, initialized, checkAuth, login, logout } = useAuth()
```

* `checkAuth()` — calls `GET /auth/me`; sets `user` on success, clears it on failure (401). Called once by the router guard before the first navigation.
* `login(email, password)` — fetches the CSRF cookie, then calls the login endpoint.
* `logout()` — calls the logout endpoint and clears local state.

## Route guard

`router/index.ts` defines a global `beforeEach` guard:
