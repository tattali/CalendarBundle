<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Request;

use CalendarBundle\Exception\InvalidDateException;
use CalendarBundle\Request\RequestParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RequestParserQueryStringTest extends TestCase
{
    public function testGetReadsDatesAndFiltersFromQueryString(): void
    {
        $request = Request::create('/fc-load-events?start=2024-01-01&end=2024-01-31&filters=%7B%22category%22%3A%22meeting%22%7D');

        $params = (new RequestParser())->parse($request);

        self::assertSame('2024-01-01', $params->start->format('Y-m-d'));
        self::assertSame('2024-01-31', $params->end->format('Y-m-d'));
        self::assertSame(['category' => 'meeting'], $params->filters);
    }

    public function testHeadReadsDatesAndFiltersFromQueryString(): void
    {
        $request = Request::create('/fc-load-events?start=2024-02-01&end=2024-02-29&filters=%7B%22category%22%3A%22holiday%22%7D', method: 'HEAD');

        $params = (new RequestParser())->parse($request);

        self::assertSame('2024-02-01', $params->start->format('Y-m-d'));
        self::assertSame('2024-02-29', $params->end->format('Y-m-d'));
        self::assertSame(['category' => 'holiday'], $params->filters);
    }

    public function testPostStillReadsBody(): void
    {
        $request = Request::create('/fc-load-events?start=2020-01-01&end=2020-01-31', method: 'POST', parameters: [
            'start' => '2024-03-01',
            'end' => '2024-03-31',
            'filters' => '{"category":"work"}',
        ]);

        $params = (new RequestParser())->parse($request);

        self::assertSame('2024-03-01', $params->start->format('Y-m-d'));
        self::assertSame('2024-03-31', $params->end->format('Y-m-d'));
        self::assertSame(['category' => 'work'], $params->filters);
    }

    public function testGetIgnoresBodyParameters(): void
    {
        $request = Request::create('/fc-load-events', method: 'GET');
        $request->request->set('start', '2024-01-01');
        $request->request->set('end', '2024-01-31');

        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('Query parameter "start" is required');

        (new RequestParser())->parse($request);
    }
}
