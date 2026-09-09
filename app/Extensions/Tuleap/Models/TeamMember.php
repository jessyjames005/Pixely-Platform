<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TeamMember extends Model
{
    use HasFactory;

    protected $table = 'tuleap_team_members';

    protected $fillable = [
        'project_id',
        'name',
        'tuleap_username',
    ];

    protected $casts = [
        'project_id' => 'integer',
    ];

    public function cafRecords(): HasMany
    {
        return $this->hasMany(CafRecord::class, 'member_id');
    }

    public function retroActions(): HasMany
    {
        return $this->hasMany(RetroAction::class, 'member_id');
    }
}