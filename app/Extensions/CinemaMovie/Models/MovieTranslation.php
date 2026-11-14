<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MovieTranslation extends Model
{
    protected $table = 'cinema_movie_translations';

    protected $fillable = ['movie_id', 'locale', 'title', 'overview', 'tagline'];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
