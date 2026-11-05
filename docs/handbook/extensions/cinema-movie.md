# CinemaMovie Reference Extension

CinemaMovie is Pixely's reference extension for the SDK v2. This guide records
the extension-owned patterns as they are implemented, with links to their code.

## Extension entry point

`app/Extensions/CinemaMovie/extension.php` is the discovery manifest. The
`CinemaMovieExtension` class declares its current capabilities and permissions:

- Navigation and API route declarations use the Core SDK contracts.
- `ExtensionTranslatableInterface` exposes the extension's `lang/` catalog to
  the Translations extension.
- `ExtensionUpgradableInterface` declares versioned data-preserving upgrades.

The API route file is registered by Core under `/api/v1` and the `api` surface.
Keep authentication and permission middleware on each protected route. The
extension's package version is `1.1.0`; the platform roadmap's `v0.8.0` heading
is a platform release milestone, not this package version.

## Permissions and translations

Movie permissions follow `<domain>.<object>.<action>`:

- `cinema-movie.movies.view`
- `cinema-movie.movies.manage`
- `cinema-movie.movies.delete`

The `1.1.0` upgrade step renames the original scaffold's `items.*` permissions
and carries existing role and direct-user grants to the new names. The
translation catalogs live in `app/Extensions/CinemaMovie/lang/{en,fr}` and are
loaded by the extension service provider. Frontend labels use the shared i18n
catalog through `@shared/plugins/i18n`.

The admin navigation label and routes remain declared through the server-side
extension capabilities and Core's static frontend route allowlist. CinemaMovie
does not need a separate local navigation registry entry.

## Next implementation steps

The current API endpoint is still a scaffold response. Domain models and their
JSON:API resources will be added in the next lot. Put future database migrations
under `app/Extensions/CinemaMovie/Database/Migrations` and run them with Pixely's
[extension migration commands](../core/extension-management.md#extension-migrations),
not Laravel's global migration discovery.
