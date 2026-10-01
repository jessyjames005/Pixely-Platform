<?php

declare(strict_types=1);

namespace App\JsonApi\V1;

use LaravelJsonApi\Core\Resources\JsonApiResource;

final class DocumentResource extends JsonApiResource
{
    public static function make($schema, string $id, array $attributes): self
    {
        return new self($schema, (object) [
            'id' => $id,
            'attributes' => $attributes,
        ]);
    }

    public function id(): string
    {
        return (string) $this->resource->id;
    }

    public function attributes($request): iterable
    {
        $allowed = [];
        foreach ($this->schema->attributes() as $field) {
            $allowed[$field->name()] = true;
        }

        return array_intersect_key($this->resource->attributes, $allowed);
    }
}
