<?php

namespace Tests\Unit\Core\Api\Query;

use App\Core\Api\Query\FilterOperator;
use App\Core\Api\Query\FilterParser;
use PHPUnit\Framework\TestCase;

final class FilterParserTest extends TestCase
{
    public function test_it_parses_a_single_filter_expression(): void
    {
        $parser = new FilterParser();

        $expressions = $parser->parse('number.greater.1');

        self::assertCount(1, $expressions);

        self::assertSame('number', $expressions[0]->field);
        self::assertSame(FilterOperator::Greater, $expressions[0]->operator);
        self::assertSame('1', $expressions[0]->value);
    }

    public function test_it_parses_multiple_filter_expressions(): void
    {
        $parser = new FilterParser();

        $expressions = $parser->parse(
            'number.greater.1,title.contains.sun'
        );

        self::assertCount(2, $expressions);

        self::assertSame('number', $expressions[0]->field);
        self::assertSame(FilterOperator::Greater, $expressions[0]->operator);
        self::assertSame('1', $expressions[0]->value);

        self::assertSame('title', $expressions[1]->field);
        self::assertSame(FilterOperator::Contains, $expressions[1]->operator);
        self::assertSame('sun', $expressions[1]->value);
    }

    public function test_it_parses_a_between_expression(): void
    {
        $parser = new FilterParser();

        $expressions = $parser->parse(
            'created_date.between.2010-03-02 08:10:00|2010-03-03 18:30:00'
        );

        self::assertCount(1, $expressions);

        self::assertSame('created_date', $expressions[0]->field);
        self::assertSame(FilterOperator::Between, $expressions[0]->operator);

        self::assertSame(
            [
                '2010-03-02 08:10:00',
                '2010-03-03 18:30:00',
            ],
            $expressions[0]->value
        );
    }

    public function test_it_rejects_an_unknown_operator(): void
    {
        $parser = new FilterParser();

        $this->expectException(\ValueError::class);

        $parser->parse('number.unknown.1');
    }

    public function test_it_rejects_an_expression_without_a_field(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse('.greater.1');
    }

    public function test_it_rejects_an_expression_without_an_operator(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse('number');
    }

    public function test_it_rejects_between_without_exactly_two_values(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse('created_date.between.2010-03-02 08:10:00');
    }

    public function test_it_rejects_between_with_more_than_two_values(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse(
            'created_date.between.2010-03-02 08:10:00|2010-03-03 18:30:00|2010-03-04 12:00:00'
        );
    }

    public function test_it_parses_is_null_without_a_value(): void
    {
        $parser = new FilterParser();

        $expressions = $parser->parse('deleted_at.isNull');

        self::assertCount(1, $expressions);
        self::assertSame('deleted_at', $expressions[0]->field);
        self::assertSame(FilterOperator::IsNull, $expressions[0]->operator);
        self::assertNull($expressions[0]->value);
    }

    public function test_it_parses_is_not_null_without_a_value(): void
    {
        $parser = new FilterParser();

        $expressions = $parser->parse('deleted_at.isNotNull');

        self::assertCount(1, $expressions);
        self::assertSame(FilterOperator::IsNotNull, $expressions[0]->operator);
        self::assertNull($expressions[0]->value);
    }

    public function test_it_parses_multiple_values_with_in(): void
    {
        $parser = new FilterParser();

        $expressions = $parser->parse('status.in.active|pending');

        self::assertCount(1, $expressions);
        self::assertSame(FilterOperator::In, $expressions[0]->operator);
        self::assertSame(
            ['active', 'pending'],
            $expressions[0]->value
        );
    }

    public function test_it_parses_multiple_values_with_not_in(): void
    {
        $parser = new FilterParser();

        $expressions = $parser->parse('status.notIn.active|deleted');

        self::assertCount(1, $expressions);
        self::assertSame(FilterOperator::NotIn, $expressions[0]->operator);
        self::assertSame(
            ['active', 'deleted'],
            $expressions[0]->value
        );
    }

    public function test_it_rejects_an_empty_value(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse('title.contains.');
    }

    public function test_it_rejects_an_empty_value_inside_in(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse('status.in.active|');
    }

    public function test_it_rejects_an_empty_value_inside_between(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse('created_date.between.2010-03-02 08:10:00|');
    }

    public function test_it_parses_equal_operator(): void
    {
        $expression = (new FilterParser())->parse('status.equal.active')[0];

        self::assertSame('status', $expression->field);
        self::assertSame(FilterOperator::Equal, $expression->operator);
        self::assertSame('active', $expression->value);
    }

    public function test_it_parses_less_operator(): void
    {
        $expression = (new FilterParser())->parse('number.less.10')[0];

        self::assertSame(FilterOperator::Less, $expression->operator);
        self::assertSame('10', $expression->value);
    }

    public function test_it_parses_greater_or_equal_operator(): void
    {
        $expression = (new FilterParser())->parse('number.greaterOrEqual.10')[0];

        self::assertSame(FilterOperator::GreaterOrEqual, $expression->operator);
        self::assertSame('10', $expression->value);
    }

    public function test_it_parses_begin_with_operator(): void
    {
        $expression = (new FilterParser())->parse('title.beginWith.Sun')[0];

        self::assertSame(FilterOperator::BeginWith, $expression->operator);
        self::assertSame('Sun', $expression->value);
    }

    public function test_it_parses_end_with_operator(): void
    {
        $expression = (new FilterParser())->parse('title.endWith.jpg')[0];

        self::assertSame(FilterOperator::EndWith, $expression->operator);
        self::assertSame('jpg', $expression->value);
    }

    public function test_it_preserves_dots_inside_a_value(): void
    {
        $expression = (new FilterParser())->parse(
            'filename.equal.photo.2026.jpg'
        )[0];

        self::assertSame('filename', $expression->field);
        self::assertSame(FilterOperator::Equal, $expression->operator);
        self::assertSame('photo.2026.jpg', $expression->value);
    }

    public function test_it_rejects_an_empty_expression(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse('number.greater.1,');
    }

    public function test_it_rejects_an_empty_expression_at_the_beginning(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse(',number.greater.1');
    }

    public function test_it_rejects_an_empty_filter_expression(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse('');
    }

    public function test_it_parses_all_value_operators(): void
    {
        $parser = new FilterParser();

        $operators = [
            FilterOperator::Equal,
            FilterOperator::NotEqual,
            FilterOperator::Less,
            FilterOperator::LessOrEqual,
            FilterOperator::Greater,
            FilterOperator::GreaterOrEqual,
            FilterOperator::Contains,
            FilterOperator::StrictEqual,
            FilterOperator::DoNotContains,
            FilterOperator::BeginWith,
            FilterOperator::DoNotBeginWith,
            FilterOperator::EndWith,
            FilterOperator::DoNotEndWith,
        ];

        foreach ($operators as $operator) {
            $expressions = $parser->parse(
                'field.' . $operator->value . '.value'
            );

            self::assertCount(1, $expressions);
            self::assertSame($operator, $expressions[0]->operator);
            self::assertSame('value', $expressions[0]->value);
        }
    }

    public function test_it_parses_all_no_value_operators(): void
    {
        $parser = new FilterParser();

        $operators = [
            FilterOperator::IsNull,
            FilterOperator::IsNotNull,
            FilterOperator::IsEmpty,
            FilterOperator::IsNotEmpty,
        ];

        foreach ($operators as $operator) {
            $expressions = $parser->parse(
                'field.' . $operator->value
            );

            self::assertCount(1, $expressions);
            self::assertSame($operator, $expressions[0]->operator);
            self::assertNull($expressions[0]->value);
        }
    }

    public function test_it_preserves_spaces_inside_a_value(): void
    {
        $expression = (new FilterParser())->parse(
            'title.contains.Summer holiday'
        )[0];

        self::assertSame('Summer holiday', $expression->value);
    }

    public function test_it_preserves_date_time_values(): void
    {
        $expression = (new FilterParser())->parse(
            'created_date.equal.2010-03-02 08:10:00'
        )[0];

        self::assertSame(
            '2010-03-02 08:10:00',
            $expression->value
        );
    }

    public function test_it_rejects_an_empty_between_value(): void
    {
        $parser = new FilterParser();

        $this->expectException(\InvalidArgumentException::class);

        $parser->parse(
            'created_date.between.|2010-03-03 18:30:00'
        );
    }
}
