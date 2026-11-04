<?php

declare(strict_types=1);

namespace App\Core\Registry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 */
final class RegistryPackage extends Model
{
    protected $fillable = [
        'type',
        'package_id',
        'name',
    ];

    /** @return HasMany<RegistryRelease, $this> */
    public function releases(): HasMany
    {
        return $this->hasMany(RegistryRelease::class);
    }
}
