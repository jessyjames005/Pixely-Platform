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
POST    /api/v1/auth/login                    (public)
POST    /api/v1/auth/two-factor-challenge     (public, needs a pending login)
POST    /api/v1/auth/forgot-password          (public, throttled)
POST    /api/v1/auth/reset-password           (public, throttled)
POST    /api/v1/auth/logout                   (auth:sanctum)
GET     /api/v1/auth/me                       (auth:sanctum)
PUT     /api/v1/auth/password                 (auth:sanctum)
GET     /api/v1/auth/two-factor               (auth:sanctum)
POST    /api/v1/auth/two-factor               (auth:sanctum, password)
POST    /api/v1/auth/two-factor/confirm       (auth:sanctum)
POST    /api/v1/auth/two-factor/recovery-codes (auth:sanctum, password)
DELETE  /api/v1/auth/two-factor               (auth:sanctum, password)
GET     /sanctum/csrf-cookie                  (provided by Sanctum)
```

`AuthController` (`App\Core\Auth\Http\Controllers\AuthController`):

* `login()` — validates credentials without starting a session, then logs the user in (`remember: true` issues a persistent "remember me" cookie), regenerates the session, and returns the authenticated user as a strict JSON:API `user` resource object. Returns a `401 INVALID_CREDENTIALS` error on failure. Only failed attempts count towards the rate limit (5 per minute per email and IP, then `429 TOO_MANY_ATTEMPTS`). When the account has two-factor enabled, no session is started: the response is a meta-only document `{"meta": {"two_factor_required": true}}` and the client must call the challenge endpoint within 5 minutes.
* `logout()` — logs out the `web` guard, invalidates the session, regenerates the CSRF token, returns `204`.
* `me()` — returns the currently authenticated user, or `401` (via the `auth:sanctum` middleware) if there is none.

## Password recovery

`POST /auth/forgot-password` always answers `204`, whether or not the address belongs to an account, so it cannot be used to enumerate users. The email links to the SPA (`/reset-password?token=…&email=…`, configured in `AuthServiceProvider`). `POST /auth/reset-password` consumes the token (single use), sets the new password and rotates the remember token, which signs out every "remember me" session of the account. Invalid and expired tokens, and unknown addresses, all return `422 INVALID_RESET_TOKEN`.

## Two-factor authentication

TOTP (RFC 6238: HMAC-SHA1, 6 digits, 30 s), implemented in `TotpService` with no third-party dependency and checked against the RFC test vectors.

* **Enrolment is two-step.** `POST /auth/two-factor` (current password required) generates an unconfirmed secret; two-factor only becomes active when `POST /auth/two-factor/confirm` receives a valid code, which also returns eight recovery codes — shown once.
* **Login challenge.** After the password step the half-authenticated user is held in the session (`TwoFactorService::SESSION_KEY`). `POST /auth/two-factor-challenge` accepts `code` (authenticator) or `recovery_code`. Five wrong attempts discard the pending login and return `429`.
* **QR code.** The setup screen renders the `otpauth://` URI as an inline SVG QR code. The encoder (`app/Core/Auth/resources/js/utils/qrcode.ts`) is dependency-free: byte mode, error correction level M, versions 1–40. It was validated by decoding generated codes with an independent QR reader across every version; `qrcode.test.ts` keeps a recorded matrix as a regression guard.
* **Replay protection.** The last accepted time step is stored; a code can be used once.
* **Recovery codes** are single-use, stored as keyed HMACs, and regenerated (old set invalidated) via `POST /auth/two-factor/recovery-codes`.
* **At rest.** `two_factor_secret` and `two_factor_recovery_codes` use encrypted casts and are `#[Hidden]` on the `User` model; they never appear in API payloads. `GET /auth/me` exposes only `two_factor_enabled`.
* **Sensitive actions** (enrol, disable, regenerate) re-check the account password.

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
