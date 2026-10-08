<?php

declare(strict_types=1);

namespace App\Core\Users\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Stores a generic favorite owned by exactly one platform user. */
final class UserFavorite extends Model
{
    protected $table = 'user_favorites';

    protected $fillable = ['user_id', 'resource_type', 'resource_id', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
