<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GenreTranslation extends Model
{
    protected $table = 'cinema_genre_translations';

    protected $fillable = ['genre_id', 'locale', 'name'];

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }
}
