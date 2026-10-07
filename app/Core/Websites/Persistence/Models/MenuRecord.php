<?php

declare(strict_types=1);

namespace App\Core\Websites\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent persistence model for website menus.
 *
 * @property string $id
 * @property string $name
 * @property string $code
 * @property \Illuminate\Database\Eloquent\Collection<int, MenuItemRecord> $items
 */
final class MenuRecord extends Model
{
    protected $table = 'website_menus';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    /** @return HasMany<MenuItemRecord, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItemRecord::class, 'menu_id')->orderBy('sort_order');
    }
}
