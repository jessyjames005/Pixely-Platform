<?php

declare(strict_types=1);

namespace App\Core\Settings\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Platform-wide settings, stored as a single JSON blob.
 *
 * Only one row is ever expected to exist.
 */
final class PlatformSetting extends Model
{
    protected $fillable = ['settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Default values applied when no platform settings exist yet.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'site_name' => 'Pixely Platform',
            'locale' => config('pixely.default_locale'),
        ];
    }

    /**
     * Return the singleton platform settings row, creating it
     * with default values if it does not exist yet.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'settings' => self::defaults(),
        ]);
    }
}
