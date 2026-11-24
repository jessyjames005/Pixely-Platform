<?php

/**
 * Music track model — the central playable entity.
 *
 * A track supports three source types:
 * - `upload` : a file stored on the private disk (file_path/mime/size set).
 * - `local`  : a file discovered in an administrator-shared local folder.
 * - `youtube`: a remote YouTube video referenced by `youtube_video_id` with no
 *              local file (ZeffyrMusic-style video mode).
 *
 * Evolution (S13 Media Converter): a `youtube` track keeps its `youtube_video_id`
 * as provenance and may later gain a derived `file_path`/`mime`/`size` once the
 * referenced video is downloaded and converted to audio. The schema already
 * allows both to coexist, so no migration is needed when S13 ships.
 *
 * Rights and visibility are governed in the service layer: a track whose
 * `rights_status` is `unknown` can never be made `public`. As defence-in-depth
 * the model also enforces this on save, so no write path (ingestion service,
 * JSON:API admin API, or direct) can ever persist a public + unknown track.
 */

declare(strict_types=1);

namespace App\Extensions\Music\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $title
 * @property int $artist_id
 * @property int|null $album_id
 * @property int|null $track_number
 * @property int|null $disc_number
 * @property int|null $duration_ms
 * @property string $source_type
 * @property string|null $file_path
 * @property string|null $mime
 * @property int|null $size
 * @property int|null $bitrate
 * @property int|null $sample_rate
 * @property int|null $channels
 * @property string|null $sha256
 * @property string|null $youtube_video_id
 * @property string $rights_status
 * @property string|null $rights_note
 * @property string $visibility
 * @property string $status
 * @property string|null $error_code
 * @property int|null $owner_id
 * @property-read Artist $artist
 * @property-read Album|null $album
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Genre> $genres
 */
final class Track extends Model
{
    use HasFactory;

    protected $table = 'music_tracks';

    public const SOURCE_TYPES = ['upload', 'local', 'youtube'];

    public const RIGHTS_STATUSES = ['owned', 'licensed', 'open_license', 'unknown'];

    public const VISIBILITIES = ['private', 'public'];

    public const STATUSES = ['processing', 'ready', 'failed'];

    protected $fillable = [
        'title',
        'artist_id',
        'album_id',
        'track_number',
        'disc_number',
        'duration_ms',
        'source_type',
        'file_path',
        'mime',
        'size',
        'bitrate',
        'sample_rate',
        'channels',
        'sha256',
        'youtube_video_id',
        'rights_status',
        'rights_note',
        'visibility',
        'status',
        'error_code',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'track_number' => 'integer',
            'disc_number' => 'integer',
            'duration_ms' => 'integer',
            'size' => 'integer',
            'bitrate' => 'integer',
            'sample_rate' => 'integer',
            'channels' => 'integer',
        ];
    }

    /**
     * Defence-in-depth rights gate: a track with unknown rights can never be
     * stored as public, regardless of the write path (service, admin API or
     * direct). The ingestion service enforces the same rule; this guarantees it
     * holds even if a future caller bypasses the service.
     */
    protected static function booted(): void
    {
        static::saving(function (Track $track): void {
            if ($track->rights_status === 'unknown' && $track->visibility === 'public') {
                $track->visibility = 'private';
            }
        });
    }

    /**
     * A track is only publicly visible when it is explicitly marked public AND
     * its rights status is known (never `unknown`). This is the single source of
     * truth for the public catalogue gate.
     */
    public function isPubliclyAvailable(): bool
    {
        return $this->visibility === 'public'
            && $this->rights_status !== 'unknown';
    }

    /**
     * @return BelongsTo<Artist, $this>
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    /**
     * @return BelongsTo<Album, $this>
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsToMany<Genre, $this>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'music_genre_track', 'track_id', 'genre_id');
    }

    /**
     * @return BelongsToMany<Artist, $this>
     */
    public function featuredArtists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'music_track_artists', 'track_id', 'artist_id')
            ->withPivot('role');
    }
}
