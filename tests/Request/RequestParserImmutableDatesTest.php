<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Request;

use CalendarBundle\Request\QueryParameters;
use CalendarBundle\Request\RequestParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RequestParserImmutableDatesTest extends TestCase
{
    public function testItBuildsImmutableDates(): void
    {
        $params = new RequestParser()->parse(Request::create('/fc-load-events', 'GET', [
            'start' => '2026-10-01T00:00:00+02:00',
            'end' => '2026-11-01T00:00:00+01:00',
        ]));

        self::assertSame(\DateTimeImmutable::class, $params->start::class);
        self::assertSame(\DateTimeImmutable::class, $params->end::class);
        self::assertSame('2026-10-01T00:00:00+02:00', $params->start->format(\DateTimeInterface::ATOM));
        self::assertSame('2026-11-01T00:00:00+01:00', $params->end->format(\DateTimeInterface::ATOM));
    }

    public function testItBuildsImmutableDatesInTheRequestedTimeZone(): void
    {
        $params = new RequestParser()->parse(Request::create('/fc-load-events', 'GET', [
            'start' => '2026-10-01T00:00:00+02:00',
            'end' => '2026-11-01T00:00:00+01:00',
            'timeZone' => 'America/New_York',
        ]));

        self::assertSame(\DateTimeImmutable::class, $params->start::class);
        self::assertSame(\DateTimeImmutable::class, $params->end::class);
        self::assertSame('2026-09-30T18:00:00-04:00', $params->start->format(\DateTimeInterface::ATOM));
        self::assertSame('2026-10-31T19:00:00-04:00', $params->end->format(\DateTimeInterface::ATOM));
    }

    public function testQueryParametersCopyTheDateTimesTheyAreGiven(): void
    {
        $start = new \DateTime('2026-10-01 00:00:00');
        $end = new \DateTime('2026-11-01 00:00:00');

        $params = new QueryParameters($start, $end, []);
        $start->modify('+1 day');
        $end->setTime(12, 0);

        self::assertSame(\DateTimeImmutable::class, $params->start::class);
        self::assertSame(\DateTimeImmutable::class, $params->end::class);
        self::assertSame('2026-10-01 00:00:00', $params->start->format('Y-m-d H:i:s'));
        self::assertSame('2026-11-01 00:00:00', $params->end->format('Y-m-d H:i:s'));
    }

    public function testQueryParametersKeepTheImmutableDatesTheyAreGiven(): void
    {
        $start = new \DateTimeImmutable('2026-10-01 00:00:00');
        $end = new \DateTimeImmutable('2026-11-01 00:00:00');

        $params = new QueryParameters($start, $end, []);

        self::assertSame($start, $params->start);
        self::assertSame($end, $params->end);
    }
}
