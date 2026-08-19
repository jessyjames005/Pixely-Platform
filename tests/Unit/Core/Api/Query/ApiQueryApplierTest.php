<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Api\Query;

use App\Core\Api\Query\ApiQuery;
use App\Core\Api\Query\ApiQueryApplier;
use App\Core\Api\Query\FilterExpression;
use App\Core\Api\Query\FilterOperator;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

function createApiQuery(
    array $filters = [],
    array $sorts = [],
    int $limit = 30,
    int $offset = 0,
): ApiQuery {
    return new ApiQuery(
        filters: $filters,
        sorts: $sorts,
        limit: $limit,
        offset: $offset,
        relationships: [],
    );
}

it('applies an equal filter', function () {
    $query = Mockery::mock(Builder::class);

    $query
        ->shouldReceive('where')
        ->once()
        ->with('title', '=', 'Sunset')
        ->andReturnSelf();

    $query
        ->shouldReceive('limit')
        ->once()
        ->with(30)
        ->andReturnSelf();

    $query
        ->shouldReceive('offset')
        ->once()
        ->with(0)
        ->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'title',
            operator: FilterOperator::Equal,
            value: 'Sunset',
        ),
    ]);

    $result = (new ApiQueryApplier())->apply($query, $apiQuery);

    expect($result)->toBe($query);
});

it('applies an in filter', function () {
    $query = Mockery::mock(Builder::class);

    $query
        ->shouldReceive('whereIn')
        ->once()
        ->with('status', ['active', 'pending'])
        ->andReturnSelf();

    $query
        ->shouldReceive('limit')
        ->once()
        ->with(30)
        ->andReturnSelf();

    $query
        ->shouldReceive('offset')
        ->once()
        ->with(0)
        ->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'status',
            operator: FilterOperator::In,
            value: ['active', 'pending'],
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies a null filter', function () {
    $query = Mockery::mock(Builder::class);

    $query
        ->shouldReceive('whereNull')
        ->once()
        ->with('deleted_at')
        ->andReturnSelf();

    $query
        ->shouldReceive('limit')
        ->once()
        ->with(30)
        ->andReturnSelf();

    $query
        ->shouldReceive('offset')
        ->once()
        ->with(0)
        ->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'deleted_at',
            operator: FilterOperator::IsNull,
            value: null,
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies sorting', function () {
    $query = Mockery::mock(Builder::class);

    $query
        ->shouldReceive('orderBy')
        ->once()
        ->with('created_at', 'desc')
        ->andReturnSelf();

    $query
        ->shouldReceive('limit')
        ->once()
        ->with(30)
        ->andReturnSelf();

    $query
        ->shouldReceive('offset')
        ->once()
        ->with(0)
        ->andReturnSelf();

    $apiQuery = createApiQuery(
        sorts: ['-created_at'],
    );

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies limit and offset', function () {
    $query = Mockery::mock(Builder::class);

    $query
        ->shouldReceive('limit')
        ->once()
        ->with(30)
        ->andReturnSelf();

    $query
        ->shouldReceive('offset')
        ->once()
        ->with(20)
        ->andReturnSelf();

    $apiQuery = createApiQuery(
        limit: 30,
        offset: 20,
    );

    $result = (new ApiQueryApplier())->apply($query, $apiQuery);

    expect($result)->toBe($query);
});

it('applies not equal filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('status', '!=', 'deleted')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'status',
            operator: FilterOperator::NotEqual,
            value: 'deleted',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies less filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('number', '<', '10')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'number',
            operator: FilterOperator::Less,
            value: '10',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies less or equal filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('number', '<=', '10')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'number',
            operator: FilterOperator::LessOrEqual,
            value: '10',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies greater filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('number', '>', '10')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'number',
            operator: FilterOperator::Greater,
            value: '10',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies greater or equal filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('number', '>=', '10')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'number',
            operator: FilterOperator::GreaterOrEqual,
            value: '10',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies not in filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('whereNotIn')
        ->once()
        ->with('status', ['deleted', 'archived'])
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'status',
            operator: FilterOperator::NotIn,
            value: ['deleted', 'archived'],
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies not null filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('whereNotNull')
        ->once()
        ->with('deleted_at')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'deleted_at',
            operator: FilterOperator::IsNotNull,
            value: null,
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies begin with filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('title', 'LIKE', 'Sun%')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'title',
            operator: FilterOperator::BeginWith,
            value: 'Sun',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies do not begin with filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('title', 'NOT LIKE', 'Sun%')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'title',
            operator: FilterOperator::DoNotBeginWith,
            value: 'Sun',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies contains filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('title', 'LIKE', '%Sun%')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'title',
            operator: FilterOperator::Contains,
            value: 'Sun',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies do not contain filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('title', 'NOT LIKE', '%Sun%')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'title',
            operator: FilterOperator::DoNotContains,
            value: 'Sun',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies end with filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('title', 'LIKE', '%Sun')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'title',
            operator: FilterOperator::EndWith,
            value: 'Sun',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies do not end with filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('title', 'NOT LIKE', '%Sun')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'title',
            operator: FilterOperator::DoNotEndWith,
            value: 'Sun',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies strict equal filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('status', '=', 'active')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'status',
            operator: FilterOperator::StrictEqual,
            value: 'active',
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies is empty filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('title', '=', '')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'title',
            operator: FilterOperator::IsEmpty,
            value: null,
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies is not empty filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('where')
        ->once()
        ->with('title', '!=', '')
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'title',
            operator: FilterOperator::IsNotEmpty,
            value: null,
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies between filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('whereBetween')
        ->once()
        ->with('created_at', [
            '2026-01-01',
            '2026-12-31',
        ])
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'created_at',
            operator: FilterOperator::Between,
            value: [
                '2026-01-01',
                '2026-12-31',
            ],
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies not between filter', function () {
    $query = Mockery::mock(Builder::class);

    $query->shouldReceive('whereNotBetween')
        ->once()
        ->with('created_at', [
            '2026-01-01',
            '2026-12-31',
        ])
        ->andReturnSelf();

    $query->shouldReceive('limit')->once()->with(30)->andReturnSelf();
    $query->shouldReceive('offset')->once()->with(0)->andReturnSelf();

    $apiQuery = createApiQuery([
        new FilterExpression(
            field: 'created_at',
            operator: FilterOperator::NotBetween,
            value: [
                '2026-01-01',
                '2026-12-31',
            ],
        ),
    ]);

    (new ApiQueryApplier())->apply($query, $apiQuery);
});

it('applies relationships', function () {
    $query = Mockery::mock(Builder::class);

    $query
        ->shouldReceive('limit')
        ->once()
        ->with(20)
        ->andReturnSelf();

    $query
        ->shouldReceive('offset')
        ->once()
        ->with(0)
        ->andReturnSelf();

    $query
        ->shouldReceive('with')
        ->once()
        ->with(['comments', 'user'])
        ->andReturnSelf();

    $apiQuery = new ApiQuery(
        filters: [],
        sorts: [],
        limit: 20,
        offset: 0,
        relationships: ['comments', 'user'],
    );

    $result = (new ApiQueryApplier())->apply($query, $apiQuery);

    expect($result)->toBe($query);
});
