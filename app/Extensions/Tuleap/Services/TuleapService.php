<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Services;

use App\Extensions\Tuleap\Contracts\TuleapRepositoryInterface;
use App\Extensions\Tuleap\Contracts\TuleapServiceInterface;
use App\Extensions\Tuleap\Models\TeamMember;
use App\Extensions\Tuleap\Models\SprintConfig;
use App\Extensions\Tuleap\Models\CafRecord;
use App\Extensions\Tuleap\Models\BurndownCache;
use App\Extensions\Tuleap\Models\AppConfig;
use App\Extensions\Tuleap\Models\RetroAction;
use App\Extensions\Tuleap\Models\Project;
use App\Extensions\Tuleap\Models\Milestone;
use Illuminate\Support\Facades\Http;
use Exception;

final class TuleapService implements TuleapServiceInterface
{
    public function __construct(private TuleapRepositoryInterface $repository) {}

    private function getTuleapUrl(): ?string
    {
        return config('services.tuleap.url') ?? env('TULEAP_URL');
    }

    private function getTuleapToken(): ?string
    {
        return $this->repository->getAppConfig('tuleap_token');
    }

    private function getTuleapUserId(): ?string
    {
        return $this->repository->getAppConfig('tuleap_user_id');
    }

    private function createHttpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $url = $this->getTuleapUrl();
        $token = $this->getTuleapToken();
        $userId = $this->getTuleapUserId();

        $headers = ['Content-Type' => 'application/json'];
        if ($token) {
            if (str_starts_with($token, 'tlp-k')) {
                $headers['X-Auth-AccessKey'] = $token;
            } else {
                $headers['X-Auth-Token'] = $token;
                if ($userId) {
                    $headers['X-Auth-UserId'] = $userId;
                }
            }
        }

        return Http::baseUrl("{$url}/api/v1")
            ->withHeaders($headers)
            ->timeout(15)
            ->throw(false);
    }