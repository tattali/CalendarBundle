<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Entity;

use CalendarBundle\Entity\Event;
use PHPUnit\Framework\TestCase;

/**
 * An all-day event exposes a start at midnight; the time it was given, or the
 * time a timed event had before setAllDay(true), must come back when the event
 * becomes timed.
 */
final class EventStartTimeTest extends TestCase
{
    public function testSetEndKeepsTheStartTimeOfAnAllDayEvent(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));
        self::assertSame('2024-01-15 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));

        $event->setEnd(new \DateTime('2024-01-15 16:00:00'));

        self::assertFalse($event->isAllDay());
        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetAllDayFalseKeepsTheStartTimeOfAnAllDayEvent(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00.250000'));

        $event->setAllDay(false);

        self::assertSame('2024-01-15 14:30:00.250000', $event->getStart()->format('Y-m-d H:i:s.u'));
        self::assertSame(new \DateTime('2024-01-15 14:30:00')->format(\DateTime::ATOM), $event->toArray()['start']);
    }

    public function testTheStartTimeComesFromTheLastSetStart(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));
        $event->setStart(new \DateTime('2024-01-16 09:15:00'));
        self::assertSame('2024-01-16 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));

        $event->setAllDay(false);

        self::assertSame('2024-01-16 09:15:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testTheGivenStartIsNeitherChangedNorTracked(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $event = new Event('Title', $start);

        $start->setTime(18, 0);
        $event->setAllDay(false);

        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
        self::assertSame('18:00:00', $start->format('H:i:s'));
    }

    public function testATimeSetWithSetStartOnTheAllDayStartIsKept(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));
        $event->setStart($event->getStart()->setTime(9, 0));

        $event->setAllDay(false);

        self::assertSame('2024-01-15 09:00:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testATimeASubclassSetOnTheAllDayStartIsKept(): void
    {
        $event = new class('Title', new \DateTime('2024-01-15 14:30:00')) extends Event {
            public function moveStart(string $modifier): void
            {
                $this->start = $this->start->modify($modifier);
            }
        };
        $event->moveStart('09:00');

        $event->setAllDay(false);

        self::assertSame('2024-01-15 09:00:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testADayASubclassChangedOnTheAllDayStartIsKept(): void
    {
        $event = new class('Title', new \DateTime('2024-01-15 14:30:00')) extends Event {
            public function moveStart(string $modifier): void
            {
                $this->start = $this->start->modify($modifier);
            }
        };
        $event->moveStart('+1 month');

        $event->setAllDay(false);

        self::assertSame('2024-02-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testTheTimeIsRestoredOnlyOnce(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));
        $event->setAllDay(false);
        $event->setStart($event->getStart()->setTime(0, 0));

        $event->setAllDay(true);
        $event->setAllDay(false);

        self::assertSame('2024-01-15 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetAllDayFalseGivesBackTheTimeThatSetAllDayTrueRemoved(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $event = new Event('Title', $start, new \DateTime('2024-01-15 16:00:00'));

        $event->setAllDay(true);
        self::assertSame('2024-01-15 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));
        self::assertSame('14:30:00', $start->format('H:i:s'));

        $event->setAllDay(false);

        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testChangesToTheGivenStartAfterSetAllDayTrueDoNotLeakIn(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $event = new Event('Title', $start, new \DateTime('2024-01-15 16:00:00'));

        $event->setAllDay(true);
        $start->setTime(18, 0);
        $event->setAllDay(false);

        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetEndGivesBackTheTimeThatSetAllDayTrueRemoved(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-15 16:00:00'));

        $event->setAllDay(true)->setEnd(new \DateTime('2024-01-15 17:00:00'));

        self::assertFalse($event->isAllDay());
        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetAllDayTrueRemembersATimeASubclassSetOnTheAllDayStart(): void
    {
        $event = new class('Title', new \DateTime('2024-01-15 14:30:00')) extends Event {
            public function moveStart(string $modifier): void
            {
                $this->start = $this->start->modify($modifier);
            }
        };
        $event->moveStart('09:00');

        $event->setAllDay(true)->setAllDay(false);

        self::assertSame('2024-01-15 09:00:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetAllDayTrueOnAnAllDayEventKeepsTheRememberedTime(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));

        $event->setAllDay(true)->setAllDay(false);

        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testATimedEventKeepsItsStartInstance(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-15 16:00:00'));
        $start = $event->getStart();

        $event->setAllDay(false);
        $event->setEnd(new \DateTime('2024-01-15 17:00:00'));

        self::assertSame($start, $event->getStart());
    }

    public function testSetStartOnATimedEventKeepsTheGivenImmutableInstance(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'));
        $event->setEnd(new \DateTime('2024-01-15 16:00:00'));
        $start = new \DateTimeImmutable('2024-01-15 10:00:00');

        $event->setStart($start);
        $event->setAllDay(false);

        self::assertSame($start, $event->getStart());
    }
}
