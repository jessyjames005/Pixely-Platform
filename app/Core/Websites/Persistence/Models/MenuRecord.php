<?php

declare(strict_types=1);

namespace App\Core\Websites\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent persistence model for a Website Engine menu.
 */
final class MenuRecord extends Model
{
    protected $table = 'website_menus';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'code',
    ];

    /**
     * Return the ordered items belonging to the menu.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItemRecord::class, 'menu_id')->orderBy('sort_order');
    }
}
