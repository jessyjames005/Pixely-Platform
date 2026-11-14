<?php

declare(strict_types=1);

namespace App\Extensions\CinemaMovie\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $movie_id
 * @property \Illuminate\Support\Carbon $scheduled_at
 * @property string $room_name
 * @property string $status
 * @property string|null $notes
 */
final class CinemaScreening extends Model
{
    use HasFactory;

    protected $table = 'cinema_screenings';

    protected $fillable = [
        'movie_id',
        'scheduled_at',
        'room_name',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id');
    }
}
