<?php

declare(strict_types=1);

namespace App\Core\Tooling\Http\Controllers;

use App\Core\Tooling\Services\LogReader;
use App\JsonApi\V1\DocumentId;
use App\JsonApi\V1\DocumentResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;

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
    public function index(Server $server): DataResponse
    {
        $resources = array_map(
            fn (array $file): DocumentResource => DocumentResource::make(
                $server->schemas()->schemaFor('log-files'),
                DocumentId::encode('log-file', $file['filename']),
                $file,
            ),
            $this->logReader->listFiles(),
        );

        return DataResponse::make($resources)
            ->withServer('v1')
            ->withMeta(['total' => count($resources)]);
    }

    /**
     * Display parsed, filterable, paginated entries from a log file.
     */
    public function show(Request $request, string $filename, Server $server): DataResponse
    {
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = max(1, min(200, (int) $request->integer('per_page', 50)));
        $level = $request->string('level')->value() ?: null;

        $result = $this->logReader->readEntries($filename, $level, $page, $perPage);
        $resources = array_map(
            fn (array $entry): DocumentResource => DocumentResource::make(
                $server->schemas()->schemaFor('log-entries'),
                DocumentId::encode('log-entry', $filename, (string) $entry['ordinal']),
                [
                    'timestamp' => $entry['timestamp'],
                    'level' => $entry['level'],
                    'message' => $entry['message'],
                ],
            ),
            $result['entries'],
        );

        return DataResponse::make($resources)
            ->withServer('v1')
            ->withMeta([
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($result['total'] / $perPage)),
                'per_page' => $perPage,
                'total' => $result['total'],
            ]);
    }
}
