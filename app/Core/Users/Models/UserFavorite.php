<?php

declare(strict_types=1);

namespace App\Core\Users\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A generic favorite owned by a user.
 *
 * Resources are identified by a stable application-level type and identifier
 * rather than an arbitrary PHP model class, keeping the Core decoupled from
 * extension implementations.
 */
final class UserFavorite extends Model
{
    protected $table = 'user_favorites';

    protected $fillable = [
        'user_id',
        'resource_type',
        'resource_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * The owning user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
