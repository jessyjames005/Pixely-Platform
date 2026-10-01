<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Controllers;

use App\Core\Settings\Models\PlatformSetting;
use App\JsonApi\V1\DocumentResource;
use App\JsonApi\V1\Settings\PlatformSettingRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use stdClass;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;

/**
 * Handles platform-wide settings API requests.
 */
#[Group('Settings & Localization', weight: 5)]
final class PlatformSettingController
{
    /**
     * Display the current platform settings.
     */
    public function show(Request $request, stdClass $platformSetting, Server $server): DataResponse
    {
        abort_unless($platformSetting->id === 'current', 404);

        return DataResponse::make(DocumentResource::make(
            $server->schemas()->schemaFor('platform-settings'),
            'current',
            PlatformSetting::current()->settings,
        ))->withServer('v1');
    }

    /**
     * Update the platform settings.
     *
     * Provided keys are merged into the existing settings;
     * omitted keys are left untouched.
     */
    public function update(PlatformSettingRequest $request, stdClass $platformSetting, Server $server): DataResponse
    {
        abort_unless($platformSetting->id === 'current', 404);

        $validated = $request->validated();

        $setting = PlatformSetting::current();
        $setting->update([
            'settings' => array_merge($setting->settings, $validated),
        ]);

        return DataResponse::make(DocumentResource::make(
            $server->schemas()->schemaFor('platform-settings'),
            'current',
            $setting->refresh()->settings,
        ))->withServer('v1');
    }
}
