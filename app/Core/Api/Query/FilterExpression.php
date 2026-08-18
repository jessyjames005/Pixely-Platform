<?php

namespace App\Core\Api\Query;

/**
 * Represents a single API filter expression.
 *
 * Example:
 *
 * filter=number.greater.1
 *
 * becomes:
 *
 * field    = number
 * operator = FilterOperator::Greater
 * value    = 1
 */
final readonly class FilterExpression
{
    public function __construct(
        public string $field,
        public FilterOperator $operator,
        public mixed $value,
    ) {
    }
}
