<?php

declare(strict_types=1);

use App\Extensions\Translations\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:translations.strings.view'])->prefix('translations')->group(function () {
    Route::get('/modules', [TranslationController::class, 'modules']);
    Route::get('/{module}/groups', [TranslationController::class, 'groups']);
    Route::get('/{module}/{group}', [TranslationController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:translations.strings.manage'])->prefix('translations')->group(function () {
    Route::put('/{module}/{group}', [TranslationController::class, 'update']);
});
