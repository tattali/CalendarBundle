<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Request;

use CalendarBundle\Exception\InvalidDateException;
use CalendarBundle\Request\RequestParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RequestParserIso8601Test extends TestCase
{
    private string $defaultTimeZone;

    protected function setUp(): void
    {
        $this->defaultTimeZone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->defaultTimeZone);
    }

    /**
     * The forms FullCalendar sends: a date, or a date and time with an
     * offset (local time zone), with "Z" (UTC) or without offset (named
     * time zone, sent with timeZone).
     */
    #[DataProvider('provideIso8601Dates')]
    public function testItAcceptsIso8601Dates(string $value, string $expected): void
    {
        $params = new RequestParser()->parse(Request::create('/fc-load-events', 'GET', [
            'start' => $value,
            'end' => '2099-01-01',
        ]));

        self::assertSame($expected, $params->start->format('Y-m-d\TH:i:s.uP'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideIso8601Dates(): iterable
    {
        yield 'date' => ['2026-10-01', '2026-10-01T00:00:00.000000+02:00'];
        yield 'date and time with an offset' => ['2026-10-01T08:30:00+05:30', '2026-10-01T08:30:00.000000+05:30'];
        yield 'date and time with a negative offset' => ['2026-10-01T08:30:00-04:00', '2026-10-01T08:30:00.000000-04:00'];
        yield 'date and time in UTC' => ['2026-10-01T08:30:00Z', '2026-10-01T08:30:00.000000+00:00'];
        yield 'date and time without offset' => ['2026-10-01T08:30:00', '2026-10-01T08:30:00.000000+02:00'];
        yield 'milliseconds' => ['2026-10-01T08:30:00.250Z', '2026-10-01T08:30:00.250000+00:00'];
        yield 'microseconds' => ['2026-10-01T08:30:00.123456+01:00', '2026-10-01T08:30:00.123456+01:00'];
        yield 'hours and minutes' => ['2026-10-01T08:30', '2026-10-01T08:30:00.000000+02:00'];
        yield 'last moment of a leap day' => ['2024-02-29T23:59:59', '2024-02-29T23:59:59.000000+01:00'];
        yield 'first moment of a year' => ['2026-01-01T00:00:00+14:00', '2026-01-01T00:00:00.000000+14:00'];
    }

    #[DataProvider('provideOtherDates')]
    public function testItRejectsDatesThatAreNotIso8601(string $value): void
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('Query parameter "start" is not a valid date');

        new RequestParser()->parse(Request::create('/fc-load-events', 'GET', [
            'start' => $value,
            'end' => '2099-01-01',
        ]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideOtherDates(): iterable
    {
        yield 'relative' => ['tomorrow'];
        yield 'relative with a date' => ['2026-10-01 +1 day'];
        yield 'timestamp' => ['@1790000000'];
        yield 'space instead of T' => ['2026-10-01 08:30:00'];
        yield 'day first' => ['01/10/2026'];
        yield 'month name' => ['1 October 2026'];
        yield 'leading text' => ['x2026-10-01'];
        yield 'trailing text' => ['2026-10-01x'];
        yield 'trailing line feed' => ["2026-10-01\n"];
        yield 'leading space' => [' 2026-10-01'];
        yield 'time zone name' => ['2026-10-01T08:30:00 Europe/Paris'];
        yield 'offset without time' => ['2026-10-01+02:00'];
        yield 'offset without colon' => ['2026-10-01T08:30:00+0200'];
        yield 'lowercase t' => ['2026-10-01t08:30:00'];
        yield 'two-digit year' => ['26-10-01'];
        yield 'month 13' => ['2026-13-01'];
        yield 'month 00' => ['2026-00-01'];
        yield 'day 00' => ['2026-10-00'];
        yield 'day 32' => ['2026-10-32'];
        yield 'day 31 of a 30-day month' => ['2026-09-31'];
        yield 'February 29 of a common year' => ['2026-02-29'];
        yield 'hour 24' => ['2026-10-01T24:00:00'];
        yield 'minute 60' => ['2026-10-01T08:60:00'];
        yield 'second 60' => ['2026-10-01T08:30:60'];
        yield 'hour only' => ['2026-10-01T08'];
        yield 'empty fraction' => ['2026-10-01T08:30:00.Z'];
        yield 'nanoseconds' => ['2026-10-01T08:30:00.123456789Z'];
        yield 'offset hour 24' => ['2026-10-01T08:30:00+24:00'];
        yield 'offset minute 60' => ['2026-10-01T08:30:00+01:60'];
    }

    public function testTheEndIsCheckedToo(): void
    {
        $this->expectException(InvalidDateException::class);
        $this->expectExceptionMessage('Query parameter "end" is not a valid date');

        new RequestParser()->parse(Request::create('/fc-load-events', 'GET', [
            'start' => '2026-10-01',
            'end' => 'next month',
        ]));
    }
}
