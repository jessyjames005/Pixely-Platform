<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BurndownCache extends Model
{
    use HasFactory;

    protected $table = 'tuleap_burndown_cache';

    protected $fillable = [
        'sprint_id',
        'day',
        'remaining_points',
    ];

    protected $casts = [
        'sprint_id' => 'integer',
        'day' => 'date',
        'remaining_points' => 'float',
    ];

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(SprintConfig::class, 'sprint_id');
    }
}