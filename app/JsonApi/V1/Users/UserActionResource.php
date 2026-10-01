<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Users;

use App\Models\User;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Core\Resources\JsonApiResource;

final class UserActionResource extends JsonApiResource
{
    /**
     * @param array<string, mixed> $allowedAttributes
     */
    public function __construct(Schema $schema, User $user, private readonly array $allowedAttributes)
    {
        parent::__construct($schema, $user);
    }

    public function attributes($request): iterable
    {
        return $this->allowedAttributes;
    }

    public function relationships($request): iterable
    {
        return [];
    }
}
