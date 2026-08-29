<?php

declare(strict_types=1);

namespace App\Core\Tooling\Http\Controllers;

use App\Core\Api\Response\ApiCollectionResponse;
use App\Core\Tooling\Services\LogReader;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only access to application log files.
 */
#[Group('System Tooling', weight: 6)]
final class LogController
{
    public function __construct(
        private readonly LogReader $logReader,
    ) {
    }

    /**
     * List available log files.
     */
    public function index(ApiCollectionResponse $apiResponse): JsonResponse
    {
        $files = $this->logReader->listFiles();

        return $apiResponse->response(
            data: $files,
            meta: ['total' => count($files)],
        );
    }

    /**
     * Display parsed, filterable, paginated entries from a log file.
     */
    public function show(Request $request, string $filename, ApiCollectionResponse $apiResponse): JsonResponse
    {
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = max(1, min(200, (int) $request->integer('per_page', 50)));
        $level = $request->string('level')->value() ?: null;

        $result = $this->logReader->readEntries($filename, $level, $page, $perPage);

        return $apiResponse->response(
            data: $result['entries'],
            meta: [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($result['total'] / $perPage)),
                'per_page' => $perPage,
                'total' => $result['total'],
            ],
        );
    }
}
