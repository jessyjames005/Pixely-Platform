<?php

use App\Core\Api\Query\ApiQuery;
use App\Core\Api\Query\FilterExpression;
use App\Core\Api\Query\FilterOperator;

it('stores API query parameters', function () {
    $filters = [
        new FilterExpression(
            field: 'number',
            operator: FilterOperator::Greater,
            value: '1',
        ),
    ];

    $query = new ApiQuery(
        filters: $filters,
        sorts: ['-created_date'],
        limit: 30,
        offset: 20,
        relationships: ['photos', 'links'],
    );

    expect($query->filters())->toBe($filters)
        ->and($query->sorts())->toBe(['-created_date'])
        ->and($query->limit())->toBe(30)
        ->and($query->offset())->toBe(20)
        ->and($query->relationships())->toBe([
            'photos',
            'links',
        ]);
});
