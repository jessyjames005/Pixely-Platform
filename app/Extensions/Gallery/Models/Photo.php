<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\PhotoFactory;

final class Photo extends Model
{
    use HasFactory;

    protected $table = 'photos';

    protected $fillable = [
        'title',
        'filename',
        'thumbnail_filename',
        'slug',
        'file_size',
    ];

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): PhotoFactory
    {
        return PhotoFactory::new();
    }
}
