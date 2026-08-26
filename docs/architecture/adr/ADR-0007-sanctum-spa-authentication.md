# ADR-0007: Sanctum SPA Authentication

## Status

Accepted

## Context

Pixely's administration interface is a Vue 3 SPA served by Laravel on the same origin as the Platform API (`/admin`, `/login` are Laravel routes; `/api/v1/*` is the API the SPA calls).

The platform needed a way to authenticate administration users that:

* fits a first-party, same-origin SPA (not a mobile app or third-party integration);
* does not require the frontend to store or manage a token;
* integrates with Laravel's existing session/guard infrastructure (`config/auth.php`, `web` guard) without introducing a parallel auth system;
* protects extension API routes (e.g. Gallery upload/update/delete) using the same mechanism, without extensions needing to know how authentication is implemented.

## Decision

Pixely uses **Laravel Sanctum in SPA mode**: session-cookie authentication, protected by Sanctum's stateful-domain CSRF handling, rather than Sanctum's API token mode.

* `AuthServiceProvider` (Core, not an extension) registers `POST /auth/login`, `POST /auth/logout`, `GET /auth/me` under `api/v1`.
* Protected routes (in any extension) use the standard `auth:sanctum` middleware.
* The frontend calls `GET /sanctum/csrf-cookie` before login, and sends `credentials: 'include'` plus the `X-XSRF-TOKEN` header on every request.
* `SANCTUM_STATEFUL_DOMAINS` / `SESSION_DOMAIN` are configured to the application's own domain.

## Alternatives Considered

### Sanctum API Tokens (Bearer)

Rejected for the administration SPA. Bearer tokens must be stored somewhere accessible to JavaScript (localStorage, memory), which reintroduces XSS token-theft exposure that the session-cookie approach avoids entirely. Token mode remains the right choice for future non-browser or third-party API consumers, but is not needed for the first-party admin.

### Plain Laravel session auth without Sanctum

Rejected. It would technically work (the SPA already runs same-origin), but Sanctum's stateful API middleware and `csrf-cookie` endpoint solve the exact SPA-authentication problem out of the box, with no custom CSRF plumbing required.

### Authentication owned by an extension

Rejected. Authentication cannot be disabled like an extension can (via `ExtensionManager::disable()`), and every extension potentially depends on it for protecting its own routes. It is registered as Core infrastructure (`bootstrap/providers.php`), the same way `AppServiceProvider` and `PixelyServiceProvider` are, rather than through the Extension Kernel's dynamic provider registration.

## Consequences

### Positive

* No token management on the client; the browser handles the session cookie automatically.
* CSRF protection is handled by Sanctum/Laravel, not reimplemented.
* Extensions protect their own routes with the standard `auth:sanctum` middleware, with no coupling to how Core implements authentication.
* Feature tests authenticate with the standard `actingAs()` helper — no token fixtures needed.

### Negative

* This approach only works because the SPA and API share an origin; a future public/third-party API consumer will need Sanctum's token mode in addition to this.
* `SANCTUM_STATEFUL_DOMAINS` must be kept in sync with the deployed domain(s), or authenticated requests will silently fail CSRF validation.

## Future Considerations

* Token-based authentication (Sanctum tokens) will likely be introduced alongside this SPA authentication once a public API SDK (v0.6.0 Platform API) is built for third-party consumers.
* Roles and permissions (v0.3.0 Core) will extend the `me()` response and may introduce an authorization middleware layer on top of `auth:sanctum`.

## Testing

Authentication is covered by feature tests exercising login (success/failure), logout, and `auth:sanctum`-protected Gallery routes via `actingAs()`. All protected-route tests must remain authenticated to avoid regressing to a 401.
