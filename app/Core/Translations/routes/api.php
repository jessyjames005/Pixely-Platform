<?php

declare(strict_types=1);

use App\Core\Translations\Http\Controllers\LocaleCatalogController;
use Illuminate\Support\Facades\Route;

/**
 * Public: the frontend needs this to render translated text before
 * login too (the login screen itself needs "Sign in", "Email", etc.).
 */
Route::get('/locales/{locale}', [LocaleCatalogController::class, 'catalog']);
