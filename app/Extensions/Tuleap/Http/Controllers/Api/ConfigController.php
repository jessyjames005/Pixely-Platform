<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConfigController
{
    public function __construct(private TuleapServiceInterface $service) {}

    public function getConfig(): JsonResponse
    {
        return response()->json([
            'tuleap_logged_in' => (bool) $this->service->getAppConfig('tuleap_token'),
            'tuleap_user_id' => $this->service->getAppConfig('tuleap_user_id'),
        ]);
    }

    public function saveConfig(Request $request): JsonResponse
    {
        if ($request->has('tuleap_token')) {
            $this->service->setAppConfig('tuleap_token', $request->string('tuleap_token'));
        }
        if ($request->has('tuleap_user_id')) {
            $this->service->setAppConfig('tuleap_user_id', $request->string('tuleap_user_id'));
        }
        return $this->getConfig();
    }

    public function getCacheInfo(): JsonResponse
    {
        return response()->json($this->service->getCacheInfo());
    }

    public function clearCache(): JsonResponse
    {
        $key = request()->query('key');
        $this->service->clearCache($key ?: null);
        return response()->json(['ok' => true]);
    }
}