<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Controllers;

use Illuminate\Http\Response;

final class GalleryController
{
    public function index(): Response
    {
        return response(
            'Gallery works!'
        );
    }
}
