<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Request;

use CalendarBundle\Exception\CalendarExceptionInterface;
use CalendarBundle\Exception\InvalidTimeZoneException;
use CalendarBundle\Request\RequestParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RequestParserTimeZoneTest extends TestCase
{
    private RequestParser $parser;
    private string $defaultTimeZone;

    protected function setUp(): void
    {
        $this->defaultTimeZone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');

        $this->parser = new RequestParser();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->defaultTimeZone);
    }

    public function testDatesWithoutOffsetAreParsedInTheRequestedTimeZone(): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2026-10-01T00:00:00',
            'end' => '2026-11-01T00:00:00',
            'timeZone' => 'America/New_York',
        ]);

        $params = $this->parser->parse($request);

        self::assertNotNull($params->timeZone);
        self::assertSame('America/New_York', $params->timeZone->getName());

        self::assertSame('America/New_York', $params->start->getTimezone()->getName());
        self::assertSame('2026-10-01T00:00:00-04:00', $params->start->format(\DateTime::ATOM));

        self::assertSame('America/New_York', $params->end->getTimezone()->getName());
        self::assertSame('2026-11-01T00:00:00-04:00', $params->end->format(\DateTime::ATOM));
    }

    public function testTimeZoneIsReadFromTheQueryStringOnGetRequests(): void
    {
        $request = Request::create('/fc-load-events?' . http_build_query([
            'start' => '2026-10-01T00:00:00',
            'end' => '2026-11-01T00:00:00',
            'timeZone' => 'America/New_York',
        ]));

        $params = $this->parser->parse($request);

        self::assertNotNull($params->timeZone);
        self::assertSame('America/New_York', $params->timeZone->getName());
        self::assertSame('2026-10-01T00:00:00-04:00', $params->start->format(\DateTime::ATOM));
    }

    public function testUtcTimeZoneIsAccepted(): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2026-10-01T00:00:00',
            'end' => '2026-11-01T00:00:00',
            'timeZone' => 'UTC',
        ]);

        $params = $this->parser->parse($request);

        self::assertNotNull($params->timeZone);
        self::assertSame('UTC', $params->timeZone->getName());
        self::assertSame('2026-10-01T00:00:00+00:00', $params->start->format(\DateTime::ATOM));
    }

    public function testDatesWithOffsetAreConvertedToTheRequestedTimeZone(): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2026-10-01T00:00:00+02:00',
            'end' => '2026-11-01T00:00:00+01:00',
            'timeZone' => 'America/New_York',
        ]);

        $params = $this->parser->parse($request);

        self::assertSame((new \DateTime('2026-10-01T00:00:00+02:00'))->getTimestamp(), $params->start->getTimestamp());
        self::assertSame('America/New_York', $params->start->getTimezone()->getName());
        self::assertSame('2026-09-30T18:00:00-04:00', $params->start->format(\DateTime::ATOM));

        self::assertSame((new \DateTime('2026-11-01T00:00:00+01:00'))->getTimestamp(), $params->end->getTimestamp());
        self::assertSame('America/New_York', $params->end->getTimezone()->getName());
        self::assertSame('2026-10-31T19:00:00-04:00', $params->end->format(\DateTime::ATOM));
    }

    public function testWithoutTimeZoneDatesUseTheServerDefault(): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2026-10-01T00:00:00',
            'end' => '2026-11-01T00:00:00',
        ]);

        $params = $this->parser->parse($request);

        self::assertNull($params->timeZone);
        self::assertSame('Europe/Paris', $params->start->getTimezone()->getName());
        self::assertSame('2026-10-01T00:00:00+02:00', $params->start->format(\DateTime::ATOM));
        self::assertSame('Europe/Paris', $params->end->getTimezone()->getName());
    }

    public function testEmptyTimeZoneIsTreatedAsAbsent(): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2026-10-01T00:00:00',
            'end' => '2026-11-01T00:00:00',
            'timeZone' => '',
        ]);

        $params = $this->parser->parse($request);

        self::assertNull($params->timeZone);
        self::assertSame('Europe/Paris', $params->start->getTimezone()->getName());
    }

    public function testInvalidTimeZoneThrowsException(): void
    {
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2026-10-01T00:00:00',
            'end' => '2026-11-01T00:00:00',
            'timeZone' => 'Mars/Olympus_Mons',
        ]);

        try {
            $this->parser->parse($request);
            self::fail('An invalid time zone must be rejected');
        } catch (InvalidTimeZoneException $e) {
            self::assertInstanceOf(CalendarExceptionInterface::class, $e);
            self::assertInstanceOf(\InvalidArgumentException::class, $e);
            self::assertSame('Query parameter "timeZone" is not a valid time zone', $e->getMessage());
        }
    }

    public function testTimeZoneWithNullByteThrowsException(): void
    {
        $this->expectException(InvalidTimeZoneException::class);
        $this->expectExceptionMessage('Query parameter "timeZone" is not a valid time zone');

        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2026-10-01T00:00:00',
            'end' => '2026-11-01T00:00:00',
            'timeZone' => "UTC\0",
        ]);

        $this->parser->parse($request);
    }
}
