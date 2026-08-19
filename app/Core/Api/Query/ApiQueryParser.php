<?php

namespace App\Core\Api\Query;

/**
 * Parses raw API query parameters into an ApiQuery value object.
 *
 * This class is responsible for translating HTTP query parameters into
 * the internal Pixely API query representation.
 *
 * Supported parameters:
 *
 * - filter
 * - sort
 * - page
 * - per_page
 * - include
 */
final class ApiQueryParser
{
    /**
     * Default number of results returned per page.
     */
    private const DEFAULT_PER_PAGE = 20;

    /**
     * Default first page number.
     */
    private const DEFAULT_PAGE = 1;

    /**
     * Maximum number of results allowed per page.
     */
    private const MAX_PER_PAGE = 100;

    public function __construct(
        private readonly FilterParser $filterParser = new FilterParser(),
    ) {}

    /**
     * Parse raw API query parameters.
     *
     * @param array<string, mixed> $parameters
     */
    public function parse(array $parameters): ApiQuery
    {
        return new ApiQuery(
            filters: $this->parseFilters($parameters),
            sorts: $this->parseSorts($parameters),
            limit: $this->parseLimit($parameters),
            offset: $this->parseOffset($parameters),
            relationships: $this->parseRelationships($parameters),
        );
    }

    /**
     * Parse the filter query parameter.
     *
     * Supports:
     *
     * filter[title]=Sunset
     *
     * and the string-based filter syntax handled by FilterParser.
     *
     * @param array<string, mixed> $parameters
     *
     * @return list<FilterExpression>
     */
    private function parseFilters(array $parameters): array
    {
        if (!isset($parameters['filter'])) {
            return [];
        }

        if (is_array($parameters['filter'])) {
            $filters = [];

            foreach ($parameters['filter'] as $field => $value) {
                if (!is_string($field)) {
                    throw new \InvalidArgumentException(
                        'Filter fields must be strings.'
                    );
                }

                if (!is_string($value)) {
                    throw new \InvalidArgumentException(
                        'Filter values must be strings.'
                    );
                }

                $filters[] = new FilterExpression(
                    field: $field,
                    operator: FilterOperator::Contains,
                    value: $value,
                );
            }

            return $filters;
        }

        if (!is_string($parameters['filter'])) {
            throw new \InvalidArgumentException(
                'The filter parameter must be a string or an array.'
            );
        }

        return $this->filterParser->parse($parameters['filter']);
    }

    /**
     * Parse the sort query parameter.
     *
     * A sort value is expected to be a comma-separated list of fields.
     *
     * A leading "-" indicates descending order.
     *
     * @param array<string, mixed> $parameters
     *
     * @return list<string>
     */
    private function parseSorts(array $parameters): array
    {
        if (!isset($parameters['sort'])) {
            return [];
        }

        if (!is_string($parameters['sort'])) {
            throw new \InvalidArgumentException(
                'The sort parameter must be a string.'
            );
        }

        if ($parameters['sort'] === '') {
            throw new \InvalidArgumentException(
                'The sort parameter cannot be empty.'
            );
        }

        $sorts = explode(',', $parameters['sort']);

        foreach ($sorts as $sort) {
            if ($sort === '') {
                throw new \InvalidArgumentException(
                    'Sort fields cannot be empty.'
                );
            }
        }

        return $sorts;
    }

    /**
     * Parse the number of results returned per page.
     *
     * The value defaults to DEFAULT_PER_PAGE and is capped at MAX_PER_PAGE.
     *
     * @param array<string, mixed> $parameters
     */
    private function parseLimit(array $parameters): int
    {
        if (!isset($parameters['per_page'])) {
            return self::DEFAULT_PER_PAGE;
        }

        $perPage = $parameters['per_page'];

        if (
            !is_int($perPage)
            && !is_string($perPage)
        ) {
            throw new \InvalidArgumentException(
                'The per_page parameter must be an integer.'
            );
        }

        if (
            is_string($perPage)
            && !preg_match('/^\d+$/', $perPage)
        ) {
            throw new \InvalidArgumentException(
                'The per_page parameter must be an integer.'
            );
        }

        $perPage = (int) $perPage;

        // A page size of zero is normalised to the minimum of one.
        if ($perPage === 0) {
            return 1;
        }

        return min($perPage, self::MAX_PER_PAGE);
    }

    /**
     * Calculate the result offset from the requested page.
     *
     * @param array<string, mixed> $parameters
     */
    private function parseOffset(array $parameters): int
    {
        if (!isset($parameters['page'])) {
            return 0;
        }

        $page = $this->parsePositiveInteger(
            $parameters['page'],
            'page',
        );

        $limit = $this->parseLimit($parameters);

        return ($page - 1) * $limit;
    }

    /**
     * Parse the relationship/include query parameter.
     *
     * Relationships are represented as a comma-separated list.
     *
     * @param array<string, mixed> $parameters
     *
     * @return list<string>
     */
    private function parseRelationships(array $parameters): array
    {
        if (!isset($parameters['include'])) {
            return [];
        }

        if (!is_string($parameters['include'])) {
            throw new \InvalidArgumentException(
                'The include parameter must be a string.'
            );
        }

        if ($parameters['include'] === '') {
            throw new \InvalidArgumentException(
                'The include parameter cannot be empty.'
            );
        }

        $relationships = explode(',', $parameters['include']);

        foreach ($relationships as $relationship) {
            if ($relationship === '') {
                throw new \InvalidArgumentException(
                    'Relationship names cannot be empty.'
                );
            }
        }

        return $relationships;
    }

    /**
     * Convert a query parameter into a positive integer.
     */
    private function parsePositiveInteger(
        mixed $value,
        string $parameter,
    ): int {
        if (
            !is_int($value)
            && !is_string($value)
        ) {
            throw new \InvalidArgumentException(
                'The ' . $parameter . ' parameter must be an integer.'
            );
        }

        if (
            is_string($value)
            && !preg_match('/^\d+$/', $value)
        ) {
            throw new \InvalidArgumentException(
                'The ' . $parameter . ' parameter must be an integer.'
            );
        }

        $integer = (int) $value;

        if ($integer < 1) {
            throw new \InvalidArgumentException(
                'The ' . $parameter . ' parameter must be greater than zero.'
            );
        }

        return $integer;
    }

    /**
     * Parse the per-page query parameter.
     *
     * Zero is accepted and normalised to one.
     */
    private function parsePerPage(mixed $value): int
    {
        if (
            !is_int($value)
            && !is_string($value)
        ) {
            throw new \InvalidArgumentException(
                'The per_page parameter must be an integer.'
            );
        }

        if (
            is_string($value)
            && !preg_match('/^\d+$/', $value)
        ) {
            throw new \InvalidArgumentException(
                'The per_page parameter must be an integer.'
            );
        }

        return (int) $value;
    }
}
