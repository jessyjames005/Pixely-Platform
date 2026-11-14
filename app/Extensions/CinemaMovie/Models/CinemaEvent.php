<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $movie_id
 * @property string $title
 * @property string $event_type
 * @property \Illuminate\Support\Carbon $start_at
 * @property \Illuminate\Support\Carbon $end_at
 * @property string $location
 * @property string|null $participants
 * @property string $status
 * @property string $color
 * @property string|null $notes
 */
final class CinemaEvent extends Model
{
    use HasFactory;

    protected $table = 'cinema_events';

    protected $fillable = [
        'movie_id',
        'title',
        'event_type',
        'start_at',
        'end_at',
        'location',
        'participants',
        'status',
        'color',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id');
    }
}
