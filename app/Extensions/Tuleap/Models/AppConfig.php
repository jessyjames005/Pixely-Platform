<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AppConfig extends Model
{
    use HasFactory;

    protected $table = 'tuleap_app_configs';

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $fillable = [
        'key',
        'value',
    ];
}