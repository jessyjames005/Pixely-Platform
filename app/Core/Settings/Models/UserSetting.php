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
            'theme' => 'system', // 'system' | 'light' | 'dark'
            'density' => 'default', // 'default' | 'comfortable' | 'compact'
            'email_notifications' => true,
        ];
    }

    /**
     * Return the given user's settings row, creating it with
     * default values if it does not exist yet. Backfills any default
     * keys missing from an already-existing row (e.g. a user whose
     * settings predate the introduction of a new preference), so older
     * rows self-heal instead of returning partial data forever.
     */
    public static function forUser(int $userId): self
    {
        $setting = static::query()->firstOrCreate(
            ['user_id' => $userId],
            ['settings' => self::defaults()],
        );

        $merged = array_merge(self::defaults(), $setting->settings);
        if ($merged !== $setting->settings) {
            $setting->update(['settings' => $merged]);
        }

        return $setting;
    }
}
