<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Controllers;

use App\Core\Api\Response\ApiResponse;
use App\Core\Settings\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles the current authenticated user's own settings.
 */
final class UserSettingController
{
    /**
     * Display the current user's settings.
     */
    public function show(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        $setting = UserSetting::forUser($request->user()->id);

        return $apiResponse->response(data: $setting->settings);
    }

    /**
     * Update the current user's settings.
     */
    public function update(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['sometimes', 'nullable', 'string', 'in:' . implode(',', array_column(config('pixely.locales'), 'code'))],
        ]);

        $setting = UserSetting::forUser($request->user()->id);
        $setting->update([
            'settings' => array_merge($setting->settings, $validated),
        ]);

        return $apiResponse->response(data: $setting->refresh()->settings);
    }
}
