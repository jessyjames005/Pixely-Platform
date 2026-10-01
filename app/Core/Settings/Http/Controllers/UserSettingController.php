<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Controllers;

use App\Core\Settings\Models\UserSetting;
use App\JsonApi\V1\DocumentResource;
use App\JsonApi\V1\Settings\UserSettingRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use stdClass;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;

/**
 * Handles the current authenticated user's own settings.
 */
#[Group('Settings & Localization', weight: 5)]
final class UserSettingController
{
    /**
     * Display the current user's settings.
     */
    public function show(Request $request, stdClass $userSetting, Server $server): DataResponse
    {
        $userSettingId = (string) $userSetting->id;
        abort_unless((string) $request->user()->id === $userSettingId, 404);
        $setting = UserSetting::forUser($request->user()->id);

        return DataResponse::make(DocumentResource::make(
            $server->schemas()->schemaFor('user-settings'),
            $userSettingId,
            $setting->settings,
        ))->withServer('v1');
    }

    /**
     * Update the current user's settings.
     */
    public function update(UserSettingRequest $request, stdClass $userSetting, Server $server): DataResponse
    {
        $userSettingId = (string) $userSetting->id;
        abort_unless((string) $request->user()->id === $userSettingId, 404);

        $validated = $request->validated();

        $setting = UserSetting::forUser($request->user()->id);
        $setting->update([
            'settings' => array_merge($setting->settings, $validated),
        ]);

        return DataResponse::make(DocumentResource::make(
            $server->schemas()->schemaFor('user-settings'),
            $userSettingId,
            $setting->refresh()->settings,
        ))->withServer('v1');
    }
}
