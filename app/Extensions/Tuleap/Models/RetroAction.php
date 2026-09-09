<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RetroAction extends Model
{
    use HasFactory;

    protected $table = 'tuleap_retro_actions';

    protected $fillable = [
        'sprint_id',
        'project_id',
        'member_id',
        'category',
        'text',
        'status',
    ];

    protected $casts = [
        'sprint_id' => 'integer',
        'project_id' => 'integer',
        'member_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(SprintConfig::class, 'sprint_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'member_id');
    }
}