<?php

namespace Tests\Unit\Core\Api\Query;

use App\Core\Api\Query\ApiQueryParser;
use App\Core\Api\Query\FilterOperator;
use PHPUnit\Framework\TestCase;

final class ApiQueryParserTest extends TestCase
{
    public function test_it_parses_filter_query_parameter(): void
    {
        $parser = new ApiQueryParser();

        $query = $parser->parse([
            'filter' => 'number.greater.1,title.contains.sun',
        ]);

        self::assertCount(2, $query->filters());

        self::assertSame(
            'number',
            $query->filters()[0]->field
        );

        self::assertSame(
            FilterOperator::Greater,
            $query->filters()[0]->operator
        );

        self::assertSame(
            '1',
            $query->filters()[0]->value
        );

        self::assertSame(
            'title',
            $query->filters()[1]->field
        );

        self::assertSame(
            FilterOperator::Contains,
            $query->filters()[1]->operator
        );

        self::assertSame(
            'sun',
            $query->filters()[1]->value
        );
    }

    public function test_it_returns_default_query_values(): void
    {
        $parser = new ApiQueryParser();

        $query = $parser->parse([]);

        self::assertSame([], $query->filters());
        self::assertSame([], $query->sorts());
        self::assertSame(20, $query->limit());
        self::assertSame(0, $query->offset());
        self::assertSame([], $query->relationships());
    }

    public function test_it_parses_sort_parameter(): void
    {
        $query = (new ApiQueryParser())->parse([
            'sort' => 'title,-created_date',
        ]);

        self::assertSame(
            ['title', '-created_date'],
            $query->sorts()
        );
    }

    public function test_it_parses_pagination_parameters(): void
    {
        $query = (new ApiQueryParser())->parse([
            'page' => '3',
            'per_page' => '20',
        ]);

        self::assertSame(20, $query->limit());
        self::assertSame(40, $query->offset());
    }

    public function test_it_caps_per_page_at_one_hundred(): void
    {
        $query = (new ApiQueryParser())->parse([
            'per_page' => '500',
        ]);

        self::assertSame(100, $query->limit());
    }

    public function test_it_parses_relationships(): void
    {
        $query = (new ApiQueryParser())->parse([
            'include' => 'photos,links',
        ]);

        self::assertSame(
            ['photos', 'links'],
            $query->relationships()
        );
    }

    public function test_it_rejects_an_invalid_page(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ApiQueryParser())->parse([
            'page' => '0',
        ]);
    }

    public function test_it_uses_one_as_the_minimum_per_page(): void
    {
        $query = (new ApiQueryParser())->parse([
            'per_page' => '0',
        ]);

        self::assertSame(1, $query->limit());
    }

    public function test_it_rejects_an_empty_sort(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ApiQueryParser())->parse([
            'sort' => '',
        ]);
    }

    public function test_it_rejects_an_empty_relationship(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ApiQueryParser())->parse([
            'include' => 'photos,',
        ]);
    }
}
