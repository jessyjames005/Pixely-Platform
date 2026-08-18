<?php

namespace App\Core\Api\Query;

/**
 * Parses the Pixely API filter syntax into FilterExpression objects.
 *
 * Supported syntax examples:
 *
 * number.greater.1
 * title.contains.sun
 * status.in.active|pending
 * created_date.between.2010-03-02 08:10:00|2010-03-03 18:30:00
 * deleted_at.isNull
 */
final class FilterParser
{
    /**
     * Operators that do not accept a value.
     *
     * @var list<FilterOperator>
     */
    private const OPERATORS_WITHOUT_VALUE = [
        FilterOperator::IsNull,
        FilterOperator::IsNotNull,
        FilterOperator::IsEmpty,
        FilterOperator::IsNotEmpty,
    ];

    /**
     * Operators that accept exactly two values.
     *
     * @var list<FilterOperator>
     */
    private const OPERATORS_WITH_TWO_VALUES = [
        FilterOperator::Between,
        FilterOperator::NotBetween,
    ];

    /**
     * Operators that accept one or more values.
     *
     * @var list<FilterOperator>
     */
    private const OPERATORS_WITH_MULTIPLE_VALUES = [
        FilterOperator::In,
        FilterOperator::NotIn,
    ];

    /**
     * Parse a complete filter expression string.
     *
     * Multiple expressions are separated by a comma.
     *
     * Example:
     *
     * number.greater.1,title.contains.sun
     *
     * @return list<FilterExpression>
     *
     * @throws \InvalidArgumentException When the filter syntax is invalid.
     * @throws \ValueError When an unknown operator is provided.
     */
    public function parse(string $filter): array
    {
        $expressions = [];

        foreach ($this->splitExpressions($filter) as $expression) {
            $expressions[] = $this->parseExpression($expression);
        }

        return $expressions;
    }

    /**
     * Split the complete filter into individual expressions.
     *
     * The comma is the expression separator.
     *
     * @return list<string>
     */
    private function splitExpressions(string $filter): array
    {
        if ($filter === '') {
            throw new \InvalidArgumentException(
                'Filter cannot be empty.'
            );
        }

        $expressions = explode(',', $filter);

        foreach ($expressions as $expression) {
            if ($expression === '') {
                throw new \InvalidArgumentException(
                    'Filter expression cannot be empty.'
                );
            }
        }

        return $expressions;
    }

    /**
     * Parse a single filter expression.
     *
     * An expression follows the structure:
     *
     * field.operator.value
     *
     * The value is optional only for operators that explicitly do not
     * accept a value, such as isNull and isNotNull.
     */
    private function parseExpression(string $expression): FilterExpression
    {
        [$field, $operator, $value] = $this->parseParts($expression);

        $this->validateField($field);
        $operator = $this->parseOperator($operator);
        $value = $this->parseValue($operator, $value);

        return new FilterExpression(
            field: $field,
            operator: $operator,
            value: $value,
        );
    }

    /**
     * Split an individual expression into its three logical components.
     *
     * The third argument of explode() is limited to 3 parts so that dots
     * appearing inside the value are preserved.
     *
     * @return array{string, string, string|null}
     */
    private function parseParts(string $expression): array
    {
        $parts = explode('.', $expression, 3);

        if (count($parts) < 2 || count($parts) > 3) {
            throw new \InvalidArgumentException(
                'Invalid filter expression: ' . $expression
            );
        }

        return [
            $parts[0],
            $parts[1],
            $parts[2] ?? null,
        ];
    }

    /**
     * Validate the filter field.
     */
    private function validateField(string $field): void
    {
        if ($field === '') {
            throw new \InvalidArgumentException(
                'Filter field cannot be empty.'
            );
        }
    }

    /**
     * Convert the textual operator into the strongly typed FilterOperator enum.
     */
    private function parseOperator(string $operator): FilterOperator
    {
        try {
            return FilterOperator::from($operator);
        } catch (\ValueError $exception) {
            throw new \ValueError(
                'Unknown filter operator: ' . $operator,
                previous: $exception,
            );
        }
    }

    /**
     * Parse and validate the value according to the operator.
     *
     * Different operator groups have different value requirements:
     *
     * - operators without values;
     * - operators requiring exactly two values;
     * - operators accepting multiple values;
     * - standard operators requiring one value.
     */
    private function parseValue(
        FilterOperator $operator,
        ?string $value,
    ): mixed {
        if ($this->operatorHasNoValue($operator)) {
            return $this->parseValueForOperatorWithoutValue($operator, $value);
        }

        $this->validateRequiredValue($operator, $value);

        if ($this->operatorHasTwoValues($operator)) {
            return $this->parseTwoValues($operator, $value);
        }

        if ($this->operatorHasMultipleValues($operator)) {
            return $this->parseMultipleValues($operator, $value);
        }

        return $value;
    }

    /**
     * Handle operators that explicitly do not accept a value.
     */
    private function parseValueForOperatorWithoutValue(
        FilterOperator $operator,
        ?string $value,
    ): mixed {
        if ($value !== null) {
            throw new \InvalidArgumentException(
                'The ' . $operator->value . ' operator does not accept a value.'
            );
        }

        return null;
    }

    /**
     * Validate that an operator requiring a value actually received one.
     */
    private function validateRequiredValue(
        FilterOperator $operator,
        ?string $value,
    ): void {
        if ($value === null || $value === '') {
            throw new \InvalidArgumentException(
                'The ' . $operator->value . ' operator requires a value.'
            );
        }
    }

    /**
     * Determine whether an operator does not accept a value.
     */
    private function operatorHasNoValue(FilterOperator $operator): bool
    {
        return in_array(
            $operator,
            self::OPERATORS_WITHOUT_VALUE,
            true,
        );
    }

    /**
     * Determine whether an operator requires exactly two values.
     */
    private function operatorHasTwoValues(FilterOperator $operator): bool
    {
        return in_array(
            $operator,
            self::OPERATORS_WITH_TWO_VALUES,
            true,
        );
    }

    /**
     * Determine whether an operator accepts multiple values.
     */
    private function operatorHasMultipleValues(FilterOperator $operator): bool
    {
        return in_array(
            $operator,
            self::OPERATORS_WITH_MULTIPLE_VALUES,
            true,
        );
    }

    /**
     * Parse an operator that requires exactly two values.
     *
     * Values are separated by the pipe character.
     *
     * Example:
     *
     * created_date.between.start|end
     *
     * @return array{string, string}
     */
    private function parseTwoValues(
        FilterOperator $operator,
        string $value,
    ): array {
        $values = explode('|', $value);

        if (
            count($values) !== 2
            || $values[0] === ''
            || $values[1] === ''
        ) {
            throw new \InvalidArgumentException(
                'The ' . $operator->value . ' operator requires exactly two non-empty values.'
            );
        }

        return $values;
    }

    /**
     * Parse an operator that accepts multiple values.
     *
     * Values are separated by the pipe character.
     *
     * Example:
     *
     * status.in.active|pending
     *
     * @return list<string>
     */
    private function parseMultipleValues(
        FilterOperator $operator,
        string $value,
    ): array {
        $values = explode('|', $value);

        if (in_array('', $values, true)) {
            throw new \InvalidArgumentException(
                'The ' . $operator->value . ' operator requires non-empty values.'
            );
        }

        return $values;
    }
}
