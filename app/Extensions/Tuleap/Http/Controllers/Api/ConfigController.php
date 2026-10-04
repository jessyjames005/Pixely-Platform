<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Controllers\Api;

use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Http\Support\TuleapDocumentRequest;
use App\Extensions\Tuleap\Http\Support\TuleapJsonApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tuleap extension configuration, cache inspection and clearing.
 */
#[Group('Tuleap', weight: 10)]
final class ConfigController
{
    public function __construct(private TuleapServiceInterface $service)
    {
    }

    /**
     * Get the Tuleap extension configuration.
     */
    public function getConfig(Server $server): DataResponse
    {
        return TuleapJsonApiResponse::one($server, 'tuleap-configs', [
            'tuleap_logged_in' => (bool) $this->service->getAppConfig('tuleap_token'),
            'tuleap_user_id' => $this->service->getAppConfig('tuleap_user_id'),
        ], 'current');
    }

    /**
     * Update the Tuleap extension configuration (Tuleap token and/or Tuleap user id).
     */
    public function saveConfig(Request $request, Server $server): DataResponse
    {
        $attributes = TuleapDocumentRequest::attributes(
            $request,
            'tuleap-configs',
            ['tuleap_token', 'tuleap_user_id'],
            [
                'tuleap_token' => ['nullable', 'string'],
                'tuleap_user_id' => ['nullable', 'string'],
            ],
        );
        if (array_key_exists('tuleap_token', $attributes)) {
            $this->service->setAppConfig('tuleap_token', (string) $attributes['tuleap_token']);
        }
        if (array_key_exists('tuleap_user_id', $attributes)) {
            $this->service->setAppConfig('tuleap_user_id', (string) $attributes['tuleap_user_id']);
        }

        return $this->getConfig($server);
    }

    /**
     * List Tuleap API cache entries with their TTL and size.
     */
    public function getCacheInfo(Server $server): DataResponse
    {
        return TuleapJsonApiResponse::many($server, 'tuleap-cache-info', $this->service->getCacheInfo());
    }

    /**
     * Invalidate the Tuleap API cache, optionally for a single key.
     */
    public function clearCache(Request $request): Response
    {
        $attributes = TuleapDocumentRequest::attributes($request, 'tuleap-cache-info', ['key'], [
            'key' => ['nullable', 'string'],
        ]);
        $this->service->clearCache($attributes['key'] ?? null);

        return response()->noContent();
    }
}
