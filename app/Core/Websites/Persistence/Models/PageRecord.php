<?php

declare(strict_types=1);

namespace App\Core\Websites\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent persistence model for a Website Engine page.
 *
 * The public DTO remains deliberately framework-light; this model is the
 * database boundary used by the Website Engine service.
 */
final class PageRecord extends Model
{
    protected $table = 'website_pages';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'slug',
        'title',
        'status',
        'template',
        'seo',
        'blocks',
    ];

    protected $casts = [
        'seo' => 'array',
        'blocks' => 'array',
    ];
}
