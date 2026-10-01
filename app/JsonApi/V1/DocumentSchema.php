<?php

declare(strict_types=1);

namespace App\JsonApi\V1;

use LaravelJsonApi\Core\Schema\Schema;
use LaravelJsonApi\Contracts\Store\Repository;
use LaravelJsonApi\NonEloquent\Fields\Attribute;
use LaravelJsonApi\NonEloquent\Fields\ID;
use App\JsonApi\V1\DocumentRepository;

abstract class DocumentSchema extends Schema
{
    public static string $model = \stdClass::class;

    protected ?string $idKeyName = 'id';

    protected static string $resourceType;

    protected static array $attributeNames = [];

    public static function resource(): string
    {
        return DocumentResource::class;
    }

    public static function type(): string
    {
        return static::$resourceType;
    }

    public function fields(): iterable
    {
        return [
            ID::make()->matchAs('[A-Za-z0-9_-]+'),
            ...array_map(Attribute::make(...), static::$attributeNames),
        ];
    }

    public function repository(): ?Repository
    {
        return DocumentRepository::make()
            ->withServer($this->server)
            ->withSchema($this);
    }
}
