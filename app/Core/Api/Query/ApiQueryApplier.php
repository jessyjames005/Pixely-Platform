<?php

declare(strict_types=1);

namespace App\Core\Api\Query;

use Illuminate\Database\Eloquent\Builder;

/**
 * Applies a parsed ApiQuery to an Eloquent query builder.
 */
final class ApiQueryApplier
{
    /**
     * Apply filters, sorting and pagination to the query.
     */
    public function apply(
        Builder $query,
        ApiQuery $apiQuery,
    ): Builder {
        $this->applyFilters($query, $apiQuery->filters());
        $this->applySorts($query, $apiQuery->sorts());
        $this->applyPagination($query, $apiQuery);

        return $query;
    }

    /**
     * Apply filters and sorting without pagination.
     *
     * This is used when the total number of matching records
     * must be calculated before applying limit and offset.
     */
    public function applyWithoutPagination(
        Builder $query,
        ApiQuery $apiQuery,
    ): Builder {
        $this->applyFilters($query, $apiQuery->filters());
        $this->applySorts($query, $apiQuery->sorts());

        return $query;
    }

    /**
     * Apply parsed filter expressions.
     *
     * Filter operators are mapped to their corresponding
     * Eloquent query builder methods.
     *
     * @param list<FilterExpression> $filters
     */
    private function applyFilters(
        Builder $query,
        array $filters,
    ): void {
        foreach ($filters as $filter) {
            match ($filter->operator) {
                FilterOperator::Equal,
                FilterOperator::StrictEqual
                => $query->where($filter->field, '=', $filter->value),

                FilterOperator::NotEqual
                => $query->where($filter->field, '!=', $filter->value),

                FilterOperator::Less
                => $query->where($filter->field, '<', $filter->value),

                FilterOperator::LessOrEqual
                => $query->where($filter->field, '<=', $filter->value),

                FilterOperator::Greater
                => $query->where($filter->field, '>', $filter->value),

                FilterOperator::GreaterOrEqual
                => $query->where($filter->field, '>=', $filter->value),

                FilterOperator::In
                => $query->whereIn($filter->field, $filter->value),

                FilterOperator::NotIn
                => $query->whereNotIn($filter->field, $filter->value),

                FilterOperator::IsNull
                => $query->whereNull($filter->field),

                FilterOperator::IsNotNull
                => $query->whereNotNull($filter->field),

                FilterOperator::BeginWith
                => $query->where($filter->field, 'LIKE', $filter->value . '%'),

                FilterOperator::DoNotBeginWith
                => $query->where($filter->field, 'NOT LIKE', $filter->value . '%'),

                FilterOperator::Contains
                => $query->where($filter->field, 'LIKE', '%' . $filter->value . '%'),

                FilterOperator::DoNotContains
                => $query->where($filter->field, 'NOT LIKE', '%' . $filter->value . '%'),

                FilterOperator::EndWith
                => $query->where($filter->field, 'LIKE', '%' . $filter->value),

                FilterOperator::DoNotEndWith
                => $query->where($filter->field, 'NOT LIKE', '%' . $filter->value),

                FilterOperator::IsEmpty
                => $query->where($filter->field, '=', ''),

                FilterOperator::IsNotEmpty
                => $query->where($filter->field, '!=', ''),

                FilterOperator::Between
                => $query->whereBetween($filter->field, $filter->value),

                FilterOperator::NotBetween
                => $query->whereNotBetween($filter->field, $filter->value),
            };
        }
    }

    /**
     * Apply parsed sort expressions.
     *
     * @param list<string> $sorts
     */
    private function applySorts(
        Builder $query,
        array $sorts,
    ): void {
        foreach ($sorts as $sort) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');

            $query->orderBy($field, $direction);
        }
    }

    /**
     * Apply pagination to the query.
     */
    private function applyPagination(
        Builder $query,
        ApiQuery $apiQuery,
    ): void {
        $query->limit($apiQuery->limit());
        $query->offset($apiQuery->offset());
    }
}
