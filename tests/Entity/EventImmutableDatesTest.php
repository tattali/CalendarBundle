<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Entity;

use CalendarBundle\Entity\Event;
use PHPUnit\Framework\TestCase;

/**
 * The event keeps its dates as \DateTimeImmutable, so neither the caller's
 * dates nor the ones the getters return can change it behind its back.
 */
final class EventImmutableDatesTest extends TestCase
{
    public function testTheGettersReturnImmutableDates(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-15 16:00:00'));

        self::assertSame(\DateTimeImmutable::class, $event->getStart()::class);
        $end = $event->getEnd();
        self::assertNotNull($end);
        self::assertSame(\DateTimeImmutable::class, $end::class);
    }

    public function testChangingADateTimePassedToTheConstructorDoesNotChangeTheEvent(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $end = new \DateTime('2024-01-15 16:00:00');
        $event = new Event('Title', $start, $end);

        $start->setTime(9, 0);
        $end->modify('+1 day');

        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-15 16:00:00', $event->getEnd()?->format('Y-m-d H:i:s'));
    }

    public function testChangingADateTimePassedToTheSettersDoesNotChangeTheEvent(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15'));
        $start = new \DateTime('2024-01-16 09:15:00');
        $end = new \DateTime('2024-01-16 10:00:00');

        $event->setEnd($end)->setStart($start);
        $start->setTime(18, 0);
        $end->modify('+1 day');

        self::assertSame('2024-01-16 09:15:00', $event->getStart()->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-16 10:00:00', $event->getEnd()?->format('Y-m-d H:i:s'));
    }

    public function testChangingTheReturnedDatesDoesNotChangeTheEvent(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-15 16:00:00'));

        $start = $event->getStart();
        $end = $event->getEnd();
        self::assertNotNull($end);

        $movedStart = $start->modify('+1 day');
        $movedEnd = $end->setTime(18, 0);

        self::assertSame('2024-01-16 14:30:00', $movedStart->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-15 18:00:00', $movedEnd->format('Y-m-d H:i:s'));
        self::assertSame($start, $event->getStart());
        self::assertSame($end, $event->getEnd());
        self::assertSame('2024-01-15 14:30:00', $start->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-15 16:00:00', $end->format('Y-m-d H:i:s'));
    }

    public function testTheSettersStoreTheImmutableDatesOfATimedEventAsGiven(): void
    {
        $event = new Event('Title', new \DateTimeImmutable('2024-01-15 14:30:00'), new \DateTimeImmutable('2024-01-15 16:00:00'));

        $start = new \DateTimeImmutable('2024-01-16 09:15:00');
        $end = new \DateTimeImmutable('2024-01-16 10:00:00');
        $event->setEnd($end)->setStart($start);

        self::assertSame($start, $event->getStart());
        self::assertSame($end, $event->getEnd());
    }

    public function testDateTimesAreCopiedWithTheirTimeAndZone(): void
    {
        $tokyo = new \DateTimeZone('Asia/Tokyo');
        $event = new Event(
            'Title',
            new \DateTime('2024-01-15 14:30:00.250000', $tokyo),
            new \DateTime('2024-01-15 16:00:00.500000', $tokyo),
        );

        self::assertSame('2024-01-15 14:30:00.250000 Asia/Tokyo', $event->getStart()->format('Y-m-d H:i:s.u e'));
        self::assertSame('2024-01-15 16:00:00.500000 Asia/Tokyo', $event->getEnd()?->format('Y-m-d H:i:s.u e'));
    }
}
