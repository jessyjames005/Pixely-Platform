<?php

declare(strict_types=1);

namespace App\Core\Users\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Stores an immutable user engagement event. */
final class UserHistoryEntry extends Model
{
    protected $table = 'user_history';

    public $timestamps = false;

    protected $fillable = ['user_id', 'resource_type', 'resource_id', 'action', 'metadata', 'occurred_at'];

    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
