<?php

declare(strict_types=1);

namespace App\Core\Registry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $version
 * @property string $package_name
 * @property \Carbon\CarbonImmutable $published_at
 */
final class RegistryRelease extends Model
{
    protected $fillable = [
        'registry_package_id',
        'version',
        'channel',
        'published_at',
        'package_name',
        'manifest_json',
        'manifest_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<RegistryPackage, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(RegistryPackage::class, 'registry_package_id');
    }
}
