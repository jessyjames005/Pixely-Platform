<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CafRecord extends Model
{
    use HasFactory;

    protected $table = 'tuleap_caf_records';

    protected $fillable = [
        'sprint_id',
        'member_id',
        'value',
    ];

    protected $casts = [
        'sprint_id' => 'integer',
        'member_id' => 'integer',
        'value' => 'float',
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