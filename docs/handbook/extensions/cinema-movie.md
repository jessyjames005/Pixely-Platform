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
The catalogue uses JSON:API resources at `/cinema-movies`, `/cinema-genres`,
`/cinema-movie-translations`, and `/cinema-genre-translations`. All require an
authenticated API user. Read operations require `movies.view`, create/update
require `movies.manage`, and deletion requires `movies.delete`. The extension's
package version is `1.1.0`; the platform roadmap's `v0.8.0` heading is a
platform release milestone, not this package version.

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

## Administration screen

The CinemaMovie admin view lists movies with pagination, shows their English
title (falling back to the original title or slug), and supports create, edit,
and confirmed delete actions. The form edits release metadata and English and
French titles and overviews. Create and edit actions require `movies.manage`;
delete controls require `movies.delete`. The screen uses the shared API,
notification, and confirmation composables with Vuetify table, form, and dialog
components. Genre administration and assigning genres in the movie form are
not part of this slice.

## Catalogue model

The current slice stores movies, genres, their many-to-many relationship, and
locale-specific movie and genre text in extension-owned tables. English and
French are the default supported locales; the `supported_locales` extension
setting controls accepted translation locales. Movie slugs are generated from
the original title when omitted. Production and publication states are
validated against the model's declared values. Movie lists use JSON:API page
pagination with a default page size of 20. A movie cannot be published until
its default supported locale has a non-empty title.

The platform extension migration runner owns installation and rollback of
these tables. Run migrations with Pixely's
[extension migration commands](../core/extension-management.md#extension-migrations),
not Laravel's global migration discovery. The runner resolves hyphenated
extension IDs to their StudlyCase directories (for example,
`cinema-movie` to `CinemaMovie`).

Media integration, TMDB import, people and credits, and the public-facing
catalogue remain later U16 work.
