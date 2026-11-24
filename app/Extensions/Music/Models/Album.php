<?php

/**
 * Music album catalogue model.
 *
 * An album groups tracks under a primary artist. `type` distinguishes the
 * release kind (album, single, ep, compilation) used by the public catalogue.
 */

declare(strict_types=1);

namespace App\Extensions\Music\Models;

use App\Extensions\Files\Models\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int $artist_id
 * @property int|null $release_year
 * @property string $type
 * @property int|null $cover_file_id
 * @property-read Artist $artist
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Track> $tracks
 */
final class Album extends Model
{
    use HasFactory;

    protected $table = 'music_albums';

    protected $fillable = [
        'title',
        'slug',
        'artist_id',
        'release_year',
        'type',
        'cover_file_id',
    ];

    protected function casts(): array
    {
        return [
            'release_year' => 'integer',
        ];
    }

    /**
     * Keep the slug in sync with the title automatically.
     */
    protected static function booted(): void
    {
        static::saving(function (Album $album): void {
            if (blank($album->slug) && filled($album->title)) {
                $album->slug = Str::slug($album->title) ?: 'album';
            }
        });
    }

    /**
     * @return BelongsTo<Artist, $this>
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    /**
     * @return HasMany<Track, $this>
     */
    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(File::class, 'cover_file_id');
    }
}
