<?php

/**
 * Music catalogue ingestion service (lot MU2).
 *
 * This is the single entry point used by the admin API, the upload pipeline and
 * the local-folder/YouTube importers to create catalogue entities. It owns the
 * business rules that must never be bypassed by callers:
 *
 * - Artists are de-duplicated by `normalized_name` (accent/case-insensitive).
 * - Albums are de-duplicated per artist by `slug`.
 * - Genres are de-duplicated by `slug` and synced through the pivot.
 * - The rights gate: a track whose `rights_status` is `unknown` can never be
 *   stored as `public` — the service forces it back to `private` so the
 *   read-side `Track::isPubliclyAvailable()` check can never be contradicted by
 *   bad input.
 *
 * The three source types (`upload`, `local`, `youtube`) each have a dedicated
 * method so the required payload per source is explicit and validated.
 */

declare(strict_types=1);

namespace App\Extensions\Music\Services;

use App\Extensions\Music\Models\Album;
use App\Extensions\Music\Models\Artist;
use App\Extensions\Music\Models\Genre;
use App\Extensions\Music\Models\Track;
use InvalidArgumentException;
use Illuminate\Support\Str;

final class CatalogIngestionService
{
    /**
     * Find or create an artist, de-duplicated by normalized name.
     */
    public function ingestArtist(string $name, ?string $bio = null, ?int $imageFileId = null): Artist
    {
        $normalized = Artist::normalizeName($name);

        /** @var Artist|null $existing */
        $existing = Artist::query()->where('normalized_name', $normalized)->first();

        if ($existing instanceof Artist) {
            return $existing;
        }

        return Artist::create([
            'name' => $name,
            'bio' => $bio,
            'image_file_id' => $imageFileId,
        ]);
    }

    /**
     * Find or create an album for an artist, de-duplicated by slug.
     */
    public function ingestAlbum(
        Artist $artist,
        string $title,
        ?int $releaseYear = null,
        string $type = 'album',
        ?int $coverFileId = null,
    ): Album {
        $slug = Str::slug($title) ?: 'album';

        /** @var Album|null $existing */
        $existing = Album::query()
            ->where('artist_id', $artist->id)
            ->where('slug', $slug)
            ->first();

        if ($existing instanceof Album) {
            return $existing;
        }

        return Album::create([
            'title' => $title,
            'artist_id' => $artist->id,
            'release_year' => $releaseYear,
            'type' => $type,
            'cover_file_id' => $coverFileId,
        ]);
    }

    /**
     * Ingest a track backed by a file uploaded through the platform.
     *
     * @param array<string, mixed> $extra Additional track attributes (album_id,
     *        track_number, rights_status, visibility, genre names, ...).
     */
    public function ingestUpload(
        Artist $artist,
        string $title,
        string $filePath,
        string $mime,
        int $size,
        array $extra = [],
    ): Track {
        return $this->createTrack($artist, array_merge($extra, [
            'title' => $title,
            'source_type' => 'upload',
            'file_path' => $filePath,
            'mime' => $mime,
            'size' => $size,
        ]));
    }

    /**
     * Ingest a track discovered in an administrator-shared local folder.
     *
     * @param array<string, mixed> $extra Additional track attributes.
     */
    public function ingestLocal(
        Artist $artist,
        string $title,
        string $filePath,
        array $extra = [],
    ): Track {
        return $this->createTrack($artist, array_merge($extra, [
            'title' => $title,
            'source_type' => 'local',
            'file_path' => $filePath,
        ]));
    }

    /**
     * Ingest a track referencing a remote YouTube video (no local file).
     *
     * @param array<string, mixed> $extra Additional track attributes.
     */
    public function ingestYoutube(
        Artist $artist,
        string $title,
        string $videoId,
        array $extra = [],
    ): Track {
        return $this->createTrack($artist, array_merge($extra, [
            'title' => $title,
            'source_type' => 'youtube',
            'youtube_video_id' => $videoId,
        ]));
    }

    /**
     * Find or create a genre by name (de-duplicated by slug).
     */
    public function ingestGenre(string $name): Genre
    {
        $slug = Str::slug($name) ?: 'genre';

        /** @var Genre|null $existing */
        $existing = Genre::query()->where('slug', $slug)->first();

        if ($existing instanceof Genre) {
            return $existing;
        }

        return Genre::create(['name' => $name]);
    }

    /**
     * Synchronize a track's genres from a list of names, creating any missing
     * genre and detaching the ones no longer present.
     *
     * @param array<int, string> $genreNames
     */
    public function syncGenres(Track $track, array $genreNames): void
    {
        $ids = [];

        foreach ($genreNames as $name) {
            $ids[] = $this->ingestGenre($name)->id;
        }

        $track->genres()->sync($ids);
    }

    /**
     * Core track creation that validates the source type and enforces the rights
     * gate before persisting.
     *
     * @param array<string, mixed> $attributes
     */
    private function createTrack(Artist $artist, array $attributes): Track
    {
        $sourceType = $attributes['source_type'] ?? 'upload';

        if (! is_string($sourceType) || ! in_array($sourceType, Track::SOURCE_TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid track source type "%s".',
                $this->describe($sourceType),
            ));
        }

        $rightsStatus = $attributes['rights_status'] ?? 'unknown';
        if (! is_string($rightsStatus) || ! in_array($rightsStatus, Track::RIGHTS_STATUSES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid rights status "%s".',
                $this->describe($rightsStatus),
            ));
        }

        $visibility = $attributes['visibility'] ?? 'private';
        if (! is_string($visibility) || ! in_array($visibility, Track::VISIBILITIES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid visibility "%s".',
                $this->describe($visibility),
            ));
        }

        // Rights gate: unknown rights can never be public. Force it back to
        // private so the public catalogue can never expose an unvetted track.
        if ($rightsStatus === 'unknown' && $visibility === 'public') {
            $visibility = 'private';
        }

        $attributes['artist_id'] = $artist->id;
        $attributes['rights_status'] = $rightsStatus;
        $attributes['visibility'] = $visibility;
        $attributes['status'] = $attributes['status']
            ?? ($sourceType === 'youtube' ? 'ready' : 'processing');

        return Track::create($attributes);
    }

    /**
     * Render a scalar value for an exception message, falling back to its type
     * when the value is not scalar (array/object) so the error stays readable.
     */
    private function describe(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : get_debug_type($value);
    }
}
