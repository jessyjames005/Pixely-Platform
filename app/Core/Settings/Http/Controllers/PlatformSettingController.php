<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Controllers;

use App\Core\Api\Response\ApiResponse;
use App\Core\Settings\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles platform-wide settings API requests.
 */
final class PlatformSettingController
{
    /**
     * Display the current platform settings.
     */
    public function show(ApiResponse $apiResponse): JsonResponse
    {
        return $apiResponse->response(
            data: PlatformSetting::current()->settings,
        );
    }

    /**
     * Update the platform settings.
     *
     * Provided keys are merged into the existing settings;
     * omitted keys are left untouched.
     */
    public function update(Request $request, ApiResponse $apiResponse): JsonResponse
    {
        $validated = $request->validate([
            'site_name' => ['sometimes', 'string', 'max:255'],
            'locale' => ['sometimes', 'string', 'in:' . implode(',', array_column(config('pixely.locales'), 'code'))],
        ]);

        $setting = PlatformSetting::current();
        $setting->update([
            'settings' => array_merge($setting->settings, $validated),
        ]);

        return $apiResponse->response(
            data: $setting->refresh()->settings,
        );
    }
}
