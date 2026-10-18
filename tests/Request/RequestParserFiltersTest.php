<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Request;

use CalendarBundle\Exception\InvalidJsonException;
use CalendarBundle\Request\RequestParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RequestParserFiltersTest extends TestCase
{
    #[DataProvider('provideNonObjectFiltersThrowInvalidJsonExceptionCases')]
    public function testNonObjectFiltersThrowInvalidJsonException(string $filters): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2024-01-01',
            'end' => '2024-01-31',
            'filters' => $filters,
        ]);

        $this->expectException(InvalidJsonException::class);
        $this->expectExceptionMessage('Query parameter "filters" must be a JSON object or array');

        new RequestParser()->parse($request);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNonObjectFiltersThrowInvalidJsonExceptionCases(): iterable
    {
        yield 'number' => ['5'];
        yield 'null' => ['null'];
        yield 'string' => ['"x"'];
        yield 'boolean' => ['true'];
    }

    public function testArrayFiltersRemainAccepted(): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2024-01-01',
            'end' => '2024-01-31',
            'filters' => '[1,2]',
        ]);

        self::assertSame([1, 2], new RequestParser()->parse($request)->filters);
    }
}
