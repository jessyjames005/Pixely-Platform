<?php

declare(strict_types=1);

namespace App\Extensions\Gallery\Http\Controllers;

use App\Extensions\Gallery\Services\GalleryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Extensions\Gallery\Http\Requests\GalleryUploadRequest;

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
        private readonly GalleryService $galleryService,
    ) {}

    /**
     * Display the gallery page.
     */
    public function index(): View
    {
        return view('gallery::index', [
            'photos' => $this->galleryService->all(),
        ]);
    }

    /**
     * Store a newly uploaded photo.
     */
    public function store(GalleryUploadRequest $request): RedirectResponse
    {
        // Stocke le fichier dans storage/app/public/photos
        $path = $request->file('image')->store('photos', 'public');

        // Enregistre la photo en base
        $this->galleryService->create([
            'title' => $request->string('title')->toString(),
            'filename' => basename($path),
        ]);


        return redirect('/gallery');
    }
}
