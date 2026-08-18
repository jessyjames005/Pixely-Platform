<?php

namespace Tests\Unit\Core\Api\Query;

use App\Core\Api\Query\FilterExpression;
use App\Core\Api\Query\FilterOperator;
use PHPUnit\Framework\TestCase;

final class FilterExpressionTest extends TestCase
{
    public function test_it_accepts_a_typed_filter_operator(): void
    {
        $expression = new FilterExpression(
            field: 'number',
            operator: FilterOperator::Greater,
            value: '1',
        );

        self::assertSame('number', $expression->field);
        self::assertSame(FilterOperator::Greater, $expression->operator);
        self::assertSame('1', $expression->value);
    }
}
