<?php

declare(strict_types=1);

use App\Core\Settings\Http\Controllers\LocaleController;
use App\Core\Settings\Http\Controllers\PlatformSettingController;
use App\Core\Settings\Http\Controllers\UserSettingController;
use Illuminate\Support\Facades\Route;

/**
 * Core settings and localization API routes.
 *
 * Registered under api/v1 by SettingsServiceProvider, following
 * the same per-module routing convention as other Core modules.
 */
Route::get('/locales', [LocaleController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings/platform', [PlatformSettingController::class, 'show']);
    Route::put('/settings/platform', [PlatformSettingController::class, 'update']);

    Route::get('/settings/user', [UserSettingController::class, 'show']);
    Route::put('/settings/user', [UserSettingController::class, 'update']);
});
