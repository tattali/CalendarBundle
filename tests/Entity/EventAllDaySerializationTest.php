<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Entity;

use CalendarBundle\Entity\Event;
use PHPUnit\Framework\TestCase;

final class EventAllDaySerializationTest extends TestCase
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

    public function testAllDayEventWithoutEndSerialisesStartAsPlainDate(): void
    {
        $event = new Event('Birthday', new \DateTime('2026-10-15 14:30:00'));

        $array = $event->toArray();

        self::assertTrue($array['allDay']);
        self::assertSame('2026-10-15', $array['start']);
        self::assertArrayNotHasKey('end', $array);
    }

    public function testAllDayEventSerialisesStartAndEndAsPlainDates(): void
    {
        $event = new Event('Holidays', new \DateTime('2026-10-15 09:00:00'), new \DateTime('2026-10-18 17:00:00'));
        $event->setAllDay(true);

        $array = $event->toArray();

        self::assertTrue($array['allDay']);
        self::assertSame('2026-10-15', $array['start']);
        self::assertSame('2026-10-18', $array['end']);
    }

    public function testTimedEventKeepsAtomWithOffset(): void
    {
        $event = new Event('Meeting', new \DateTime('2026-10-15 09:00:00'), new \DateTime('2026-10-15 10:30:00'));

        $array = $event->toArray();

        self::assertFalse($array['allDay']);
        self::assertSame('2026-10-15T09:00:00+02:00', $array['start']);
        self::assertSame('2026-10-15T10:30:00+02:00', $array['end']);
    }

    public function testAllDayEventInAZoneEastOfUtcKeepsItsCalendarDate(): void
    {
        $tokyo = new \DateTimeZone('Asia/Tokyo');
        $event = new Event('Conference', new \DateTime('2026-10-15 08:00:00', $tokyo), new \DateTime('2026-10-16 08:00:00', $tokyo));
        $event->setAllDay(true);

        $array = $event->toArray();

        // Midnight in Tokyo is still the previous day in UTC: the date must not drift.
        self::assertSame('2026-10-15', $array['start']);
        self::assertSame('2026-10-16', $array['end']);
    }
}
