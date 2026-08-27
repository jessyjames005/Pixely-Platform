<?php

declare(strict_types=1);

namespace App\Core\Settings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user settings (preferences), stored as a JSON blob.
 */
final class UserSetting extends Model
{
    protected $fillable = ['user_id', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Default values applied when a user has no settings yet.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'locale' => null, // null means "use the platform default"
        ];
    }

    /**
     * Return the given user's settings row, creating it with
     * default values if it does not exist yet.
     */
    public static function forUser(int $userId): self
    {
        return static::query()->firstOrCreate(
            ['user_id' => $userId],
            ['settings' => self::defaults()],
        );
    }
}
