<?php

declare(strict_types=1);

namespace App\Extensions\Tuleap\Http\Support;

use App\JsonApi\V1\DocumentId;
use App\JsonApi\V1\DocumentResource;
use Illuminate\Contracts\Support\Arrayable;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Responses\DataResponse;

final class TuleapJsonApiResponse
{
    public static function one(
        Server $server,
        string $type,
        mixed $attributes,
        string|int $identity,
        array $context = [],
        int $status = 200,
    ): DataResponse {
        $response = DataResponse::make(DocumentResource::make(
            $server->schemas()->schemaFor($type),
            self::id($type, $identity, $context),
            self::attributes($attributes),
        ))->withServer('v1')->withLinks(['self' => self::selfLink()]);

        return $status === 201 ? $response->didCreate() : $response;
    }

    public static function many(
        Server $server,
        string $type,
        array $items,
        array $context = [],
    ): DataResponse {
        $resources = array_map(function (mixed $item) use ($server, $type, $context): DocumentResource {
            $attributes = self::attributes($item);
            $identity = self::identity($attributes);

            return DocumentResource::make(
                $server->schemas()->schemaFor($type),
                self::id($type, $identity, $context),
                $attributes,
            );
        }, $items);

        return DataResponse::make($resources)
            ->withServer('v1')
            ->withLinks(['self' => self::selfLink()])
            ->withMeta(['total' => count($resources)]);
    }

    private static function attributes(mixed $resource): array
    {
        if ($resource instanceof Arrayable) {
            $resource = $resource->toArray();
        } elseif (is_object($resource)) {
            $resource = get_object_vars($resource);
        }

        if (! is_array($resource)) {
            return [];
        }

        return $resource;
    }

    private static function identity(array $attributes): string
    {
        foreach (['id', 'key'] as $field) {
            if (isset($attributes[$field]) && is_scalar($attributes[$field])) {
                return (string) $attributes[$field];
            }
        }

        if (isset($attributes['sprint_id'], $attributes['member_id'])) {
            return $attributes['sprint_id'] . ':' . $attributes['member_id'];
        }

        if (isset($attributes['day'])) {
            return (string) $attributes['day'];
        }

        return hash('sha256', json_encode($attributes, JSON_THROW_ON_ERROR));
    }

    private static function id(string $type, string|int $identity, array $context): string
    {
        return DocumentId::encode($type, ...array_map(strval(...), [...$context, (string) $identity]));
    }

    private static function selfLink(): string
    {
        return request()->fullUrl();
    }
}
