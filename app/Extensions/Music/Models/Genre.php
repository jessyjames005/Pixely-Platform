<?php

/**
 * Music genre taxonomy model.
 *
 * Genres are a flat, reusable taxonomy shared across tracks through the
 * `music_genre_track` pivot.
 */

declare(strict_types=1);

namespace App\Extensions\Music\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Track> $tracks
 */
final class Genre extends Model
{
    use HasFactory;

    protected $table = 'music_genres';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Keep the slug in sync with the name automatically.
     */
    protected static function booted(): void
    {
        static::saving(function (Genre $genre): void {
            if (blank($genre->slug) && filled($genre->name)) {
                $genre->slug = Str::slug($genre->name) ?: 'genre';
            }
        });
    }

    /**
     * @return BelongsToMany<Track, $this>
     */
    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'music_genre_track', 'genre_id', 'track_id');
    }
}
