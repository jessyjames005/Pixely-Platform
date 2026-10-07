<?php

declare(strict_types=1);

namespace App\Core\Websites\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent persistence model for website menu items.
 *
 * @property string $id
 * @property string $menu_id
 * @property string $type
 * @property string $title
 * @property string|null $target_url
 * @property string|null $page_id
 * @property string|null $extension_id
 * @property string|null $slug
 * @property int $sort_order
 * @property bool $active
 */
final class MenuItemRecord extends Model
{
    protected $table = 'website_menu_items';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(MenuRecord::class, 'menu_id');
    }
}
