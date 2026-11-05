<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Genre extends Model
{
    protected $table = 'cinema_genres';

    protected $fillable = ['slug'];

    protected static function booted(): void
    {
        static::creating(function (Genre $genre): void {
            if (blank($genre->slug)) {
                $genre->slug = Str::slug('genre-' . Str::uuid());
            }
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(GenreTranslation::class);
    }

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'cinema_genre_movie', 'genre_id', 'movie_id');
    }
}
