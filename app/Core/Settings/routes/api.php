<?php

declare(strict_types=1);

use App\Core\Settings\Http\Controllers\LocaleController;
use App\Core\Settings\Http\Controllers\PlatformSettingController;
use App\Core\Settings\Http\Controllers\UserSettingController;
use Illuminate\Support\Facades\Route;

/**
 * Core settings and localization API routes.
 *
 * Platform settings require settings.platform.view/manage — they
 * affect every user of the platform. User settings (own locale
 * preference) are self-service: any authenticated user may read
 * and update their own, no dedicated permission required.
 */
Route::get('/locales', [LocaleController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings/user', [UserSettingController::class, 'show']);
    Route::put('/settings/user', [UserSettingController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'permission:settings.platform.view'])->group(function () {
    Route::get('/settings/platform', [PlatformSettingController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'permission:settings.platform.manage'])->group(function () {
    Route::put('/settings/platform', [PlatformSettingController::class, 'update']);
});
