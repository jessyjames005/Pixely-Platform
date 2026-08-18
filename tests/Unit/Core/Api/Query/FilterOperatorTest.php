<?php

use App\Core\Api\Query\FilterOperator;

it('defines the supported filter operators', function () {
    expect(FilterOperator::Equal->value)->toBe('equal')
        ->and(FilterOperator::NotEqual->value)->toBe('notEqual')
        ->and(FilterOperator::Less->value)->toBe('less')
        ->and(FilterOperator::LessOrEqual->value)->toBe('lessOrEqual')
        ->and(FilterOperator::Greater->value)->toBe('greater')
        ->and(FilterOperator::GreaterOrEqual->value)->toBe('greaterOrEqual')
        ->and(FilterOperator::In->value)->toBe('in')
        ->and(FilterOperator::NotIn->value)->toBe('notIn')
        ->and(FilterOperator::IsNull->value)->toBe('isNull')
        ->and(FilterOperator::IsNotNull->value)->toBe('isNotNull')
        ->and(FilterOperator::BeginWith->value)->toBe('beginWith')
        ->and(FilterOperator::DoNotBeginWith->value)->toBe('doNotBeginWith')
        ->and(FilterOperator::Contains->value)->toBe('contains')
        ->and(FilterOperator::StrictEqual->value)->toBe('strictEqual')
        ->and(FilterOperator::DoNotContains->value)->toBe('doNotContains')
        ->and(FilterOperator::EndWith->value)->toBe('endWith')
        ->and(FilterOperator::DoNotEndWith->value)->toBe('doNotEndWith')
        ->and(FilterOperator::IsEmpty->value)->toBe('isEmpty')
        ->and(FilterOperator::IsNotEmpty->value)->toBe('isNotEmpty')
        ->and(FilterOperator::Between->value)->toBe('between')
        ->and(FilterOperator::NotBetween->value)->toBe('notBetween');
});
