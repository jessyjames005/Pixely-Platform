<?php

declare(strict_types=1);

namespace App\Core\Websites\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/** Eloquent persistence model for website pages. */
final class PageRecord extends Model
{
    protected $table = 'website_pages';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['seo' => 'array', 'blocks' => 'array'];
    }
}
