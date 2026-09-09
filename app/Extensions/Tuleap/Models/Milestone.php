<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Milestone extends Model
{
    use HasFactory;

    protected $table = 'tuleap_milestones';

    public $incrementing = false;

    protected $primaryKey = 'milestone_id';

    protected $fillable = [
        'milestone_id',
        'project_id',
        'title',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'milestone_id' => 'integer',
        'project_id' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function sprintConfig(): BelongsTo
    {
        return $this->belongsTo(SprintConfig::class, 'sprint_id', 'milestone_id');
    }
}