<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Models;

use Illuminate\Database\Eloquent\Model;

final class Photo extends Model
{
    protected $table = 'photos';

    protected $fillable = [
        'title',
        'filename',
    ];
}
