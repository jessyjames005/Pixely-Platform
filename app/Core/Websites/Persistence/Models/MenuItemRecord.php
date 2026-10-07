<?php

declare(strict_types=1);

namespace App\Core\Websites\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent persistence model for a Website Engine menu item.
 */
final class MenuItemRecord extends Model
{
    protected $table = 'website_menu_items';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'menu_id',
        'type',
        'title',
        'target_url',
        'page_id',
        'extension_id',
        'slug',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * Return the parent menu.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(MenuRecord::class, 'menu_id');
    }
}
