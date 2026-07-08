<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Http\Controllers;

use App\Extensions\Gallery\Services\GalleryService;
use Illuminate\Contracts\View\View;

/**
 * Handles HTTP requests for the Gallery extension.
 *
 * This controller delegates business logic to the GalleryService
 * and only manages the HTTP layer.
 */
final class GalleryController
{
    /**
     * Create a new Gallery controller instance.
     */
    public function __construct(
        private readonly GalleryService $gallery,
    ) {
    }

    /**
     * Display the gallery page.
     */
    public function index(): View
    {
        return view('gallery::index', [
            'photos' => $this->gallery->all(),
        ]);
    }
}
