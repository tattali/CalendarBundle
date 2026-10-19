<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Entity;

use CalendarBundle\Entity\Event;
use PHPUnit\Framework\TestCase;

final class EventSettersTest extends TestCase
{
    public function testSetStartAcceptsAnImmutableDateOnATimedEvent(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-15 18:00:00'));
        $start = new \DateTimeImmutable('2024-01-16 09:15:00.250000', new \DateTimeZone('Asia/Tokyo'));

        $event->setStart($start);

        self::assertFalse($event->isAllDay());
        self::assertSame('2024-01-16 09:15:00.250000 Asia/Tokyo', $event->getStart()->format('Y-m-d H:i:s.u e'));
        self::assertSame('2024-01-16 09:15:00.250000', $start->format('Y-m-d H:i:s.u'));
    }

    public function testSetStartAcceptsAnImmutableDateOnAnAllDayEventAndKeepsItsTime(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15'));

        $event->setStart(new \DateTimeImmutable('2024-01-16 09:15:00'));

        self::assertTrue($event->isAllDay());
        self::assertSame('2024-01-16 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));

        $event->setAllDay(false);

        self::assertSame('2024-01-16 09:15:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetEndAcceptsAnImmutableDate(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));
        $end = new \DateTimeImmutable('2024-01-15 16:00:00.500000', new \DateTimeZone('Asia/Tokyo'));

        $event->setEnd($end);

        self::assertFalse($event->isAllDay());
        self::assertSame('2024-01-15 16:00:00.500000 Asia/Tokyo', $event->getEnd()?->format('Y-m-d H:i:s.u e'));
        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetEndStillAcceptsNull(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'), new \DateTimeImmutable('2024-01-15 16:00:00'));

        $event->setEnd(null);

        self::assertTrue($event->isAllDay());
        self::assertNull($event->getEnd());
    }

    public function testSettersKeepTheGivenDateTimeInstancesOfATimedEvent(): void
    {
        $event = new Event('Title', new \DateTimeImmutable('2024-01-15 14:30:00'));
        $start = new \DateTime('2024-01-16 09:15:00');
        $end = new \DateTime('2024-01-16 10:00:00');

        $event->setEnd($end)->setStart($start);

        self::assertSame($start, $event->getStart());
        self::assertSame($end, $event->getEnd());
    }
}
