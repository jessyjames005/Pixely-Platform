<?php

declare(strict_types=1);

namespace App\Core\Users\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A generic user activity/history entry.
 */
final class UserHistoryEntry extends Model
{
    protected $table = 'user_history';

    protected $fillable = [
        'user_id',
        'resource_type',
        'resource_id',
        'action',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * The owning user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
