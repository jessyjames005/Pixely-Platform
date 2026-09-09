<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SprintConfig extends Model
{
    use HasFactory;

    protected $table = 'tuleap_sprint_configs';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'objective',
        'confidence_index',
        'pct_evolution',
        'pct_analysis',
        'pct_bug',
        'working_days',
        'velocity_per_day',
        'review_comment',
    ];

    protected $casts = [
        'id' => 'integer',
        'confidence_index' => 'integer',
        'pct_evolution' => 'integer',
        'pct_analysis' => 'integer',
        'pct_bug' => 'integer',
        'working_days' => 'integer',
        'velocity_per_day' => 'float',
    ];

    public function cafRecords(): HasMany
    {
        return $this->hasMany(CafRecord::class, 'sprint_id');
    }

    public function burndownCache(): HasMany
    {
        return $this->hasMany(BurndownCache::class, 'sprint_id');
    }

    public function retroActions(): HasMany
    {
        return $this->hasMany(RetroAction::class, 'sprint_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}