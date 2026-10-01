<?php

declare(strict_types=1);

namespace App\Core\Tooling\Http\Controllers;

use App\JsonApi\V1\DocumentId;
use App\JsonApi\V1\DocumentResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Responses\DataResponse;
use Illuminate\Support\Facades\Redis;

/**
 * Read-only (and single explicit clear action) access to Redis.
 *
 * Uses the 'tooling' Redis connection (configured with an empty
 * key prefix in config/database.php) so the admin sees the real,
 * unprefixed keyspace rather than the app's cache-prefixed view.
 *
 * This deliberately never exposes arbitrary Redis commands — only
 * key listing, key inspection, and a whole-cache flush.
 */
#[Group('System Tooling', weight: 6)]
final class RedisController
{
    /**
     * List keys matching an optional pattern, with type and TTL.
     */
    public function index(Request $request, Server $server): DataResponse
    {
        $pattern = $request->string('pattern')->value() ?: '*';
        $limit = max(1, min(500, (int) $request->integer('limit', 100)));

        $connection = Redis::connection('tooling');

        $keys = [];
        $cursor = '0';

        do {
            [$cursor, $batch] = $connection->scan($cursor, ['match' => $pattern, 'count' => 100]);

            foreach ($batch as $key) {
                $keys[] = DocumentResource::make(
                    $server->schemas()->schemaFor('cache-keys'),
                    DocumentId::encode('cache-key', $key),
                    [
                        'key' => $key,
                        'type' => $connection->type($key),
                        'ttl' => $connection->ttl($key),
                    ],
                );

                if (count($keys) >= $limit) {
                    break 2;
                }
            }
        } while ($cursor !== '0');

        return DataResponse::make($keys)
            ->withServer('v1')
            ->withMeta(['total' => count($keys), 'pattern' => $pattern]);
    }

    /**
     * Display the value stored under a specific key.
     */
    public function show(string $key, Server $server): DataResponse
    {
        $connection = Redis::connection('tooling');

        if ($connection->exists($key) === 0) {
            throw JsonApiException::error([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'title' => 'Not Found',
                'detail' => 'The requested Redis key was not found.',
            ]);
        }

        $type = $connection->type($key);

        $value = match ($type) {
            'string' => $connection->get($key),
            'list' => $connection->lrange($key, 0, -1),
            'set' => $connection->smembers($key),
            'zset' => $connection->zrange($key, 0, -1, ['withscores' => true]),
            'hash' => $connection->hgetall($key),
            default => null,
        };

        return DataResponse::make(DocumentResource::make(
            $server->schemas()->schemaFor('cache-keys'),
            DocumentId::encode('cache-key', $key),
            [
                'key' => $key,
                'type' => $type,
                'ttl' => $connection->ttl($key),
                'value' => $value,
            ],
        ))->withServer('v1');
    }

    /**
     * Delete a single key.
     */
    public function destroy(string $key): \Symfony\Component\HttpFoundation\Response
    {
        Redis::connection('tooling')->del($key);

        return response()->noContent();
    }

    /**
     * Flush the entire current Redis database.
     *
     * Deliberately separate from destroy() and behind its own
     * permission (system.cache.clear), since this is destructive
     * and irreversible across the whole cache, not a single key.
     */
    public function flush(): \Symfony\Component\HttpFoundation\Response
    {
        Redis::connection('tooling')->flushdb();

        return response()->noContent();
    }
}
