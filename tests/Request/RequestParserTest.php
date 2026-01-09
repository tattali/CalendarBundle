<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Request;

use CalendarBundle\Exception\InvalidDateException;
use CalendarBundle\Exception\InvalidJsonException;
use CalendarBundle\Request\QueryParameters;
use CalendarBundle\Request\RequestParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RequestParserTest extends TestCase
{
    private RequestParser $parser;

    protected function setUp(): void
    {
        $this->parser = new RequestParser();
    }

    public function testParseValidRequest(): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2024-01-01',
            'end' => '2024-01-31',
            'filters' => '{"category":"meeting"}',
        ]);

        $params = $this->parser->parse($request);

        self::assertInstanceOf(QueryParameters::class, $params);
        self::assertSame('2024-01-01', $params->start->format('Y-m-d'));
        self::assertSame('2024-01-31', $params->end->format('Y-m-d'));
        self::assertSame(['category' => 'meeting'], $params->filters);
    }

    public function testParseDefaultFilters(): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2024-01-01',
            'end' => '2024-01-31',
        ]);

        $params = $this->parser->parse($request);

        self::assertSame([], $params->filters);
    }

    public function testParseMissingStartThrowsException(): void
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('Query parameter "start" is required');

        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'end' => '2024-01-31',
        ]);

        $this->parser->parse($request);
    }

    public function testParseMissingEndThrowsException(): void
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('Query parameter "end" is required');

        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2024-01-01',
        ]);

        $this->parser->parse($request);
    }

    public function testParseInvalidStartDateThrowsException(): void
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('Query parameter "start" is not a valid date');

        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => 'not-a-date',
            'end' => '2024-01-31',
        ]);

        $this->parser->parse($request);
    }

    public function testParseInvalidEndDateThrowsException(): void
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('Query parameter "end" is not a valid date');

        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2024-01-01',
            'end' => 'invalid',
        ]);

        $this->parser->parse($request);
    }

    public function testParseInvalidJsonFiltersThrowsException(): void
    {
        $this->expectException(InvalidJsonException::class);
        $this->expectExceptionMessage('Query parameter "filters" is not valid JSON');

        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2024-01-01',
            'end' => '2024-01-31',
            'filters' => '{invalid}',
        ]);

        $this->parser->parse($request);
    }

    public function testParseNestedFiltersWithinDepthLimit(): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2024-01-01',
            'end' => '2024-01-31',
            'filters' => '{"level1":{"level2":{"level3":"value"}}}',
        ]);

        $params = $this->parser->parse($request);

        self::assertSame(['level1' => ['level2' => ['level3' => 'value']]], $params->filters);
    }

    public function testParseDeeplyNestedFiltersThrowsException(): void
    {
        $this->expectException(InvalidJsonException::class);

        // Depth 5 exceeds MAX_JSON_DEPTH of 4
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2024-01-01',
            'end' => '2024-01-31',
            'filters' => '{"l1":{"l2":{"l3":{"l4":{"l5":"too deep"}}}}}',
        ]);

        $this->parser->parse($request);
    }
}
