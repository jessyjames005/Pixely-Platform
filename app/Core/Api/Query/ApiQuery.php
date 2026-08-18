<?php

namespace App\Core\Api\Query;

/**
 * Represents an already parsed API query.
 *
 * This value object contains the query instructions that will later be
 * consumed by the API repository layer.
 */
final readonly class ApiQuery
{
    /**
     * @param list<FilterExpression> $filters
     * @param list<string> $sorts
     * @param list<string> $relationships
     */
    public function __construct(
        private array $filters,
        private array $sorts,
        private int $limit,
        private int $offset,
        private array $relationships,
    ) {
    }

    /**
     * Return the parsed filter expressions.
     *
     * @return list<FilterExpression>
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * @return list<string>
     */
    public function sorts(): array
    {
        return $this->sorts;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    /**
     * @return list<string>
     */
    public function relationships(): array
    {
        return $this->relationships;
    }
}
