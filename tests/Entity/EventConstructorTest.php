<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Entity;

use CalendarBundle\Entity\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EventConstructorTest extends TestCase
{
    public function testItAcceptsImmutableDates(): void
    {
        $start = new \DateTimeImmutable('2024-01-15 14:30:00.250000', new \DateTimeZone('Asia/Tokyo'));
        $end = new \DateTimeImmutable('2024-01-15 16:00:00', new \DateTimeZone('Asia/Tokyo'));

        $event = new Event('Title', $start, $end);

        self::assertFalse($event->isAllDay());
        self::assertSame('2024-01-15 14:30:00.250000 Asia/Tokyo', $event->getStart()->format('Y-m-d H:i:s.u e'));
        self::assertSame('2024-01-15 16:00:00 Asia/Tokyo', $event->getEnd()?->format('Y-m-d H:i:s e'));
        self::assertSame('2024-01-15 14:30:00.250000', $start->format('Y-m-d H:i:s.u'));
    }

    public function testAnImmutableStartMakesAnAllDayEventThatKeepsItsTime(): void
    {
        $event = new Event('Title', new \DateTimeImmutable('2024-01-15 14:30:00'));

        self::assertTrue($event->isAllDay());
        self::assertSame('2024-01-15 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));

        $event->setAllDay(false);

        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testMutableDatesOfATimedEventAreStoredAsGiven(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $end = new \DateTime('2024-01-15 16:00:00');

        $event = new Event('Title', $start, $end);

        self::assertSame($start, $event->getStart());
        self::assertSame($end, $event->getEnd());
    }

    #[DataProvider('provideAllDayCases')]
    public function testAllDayArgument(?bool $allDay, bool $withEnd, bool $expectedAllDay, string $expectedStart): void
    {
        $event = new Event(
            'Title',
            new \DateTimeImmutable('2024-01-15 14:30:00'),
            $withEnd ? new \DateTimeImmutable('2024-01-18') : null,
            allDay: $allDay,
        );

        self::assertSame($expectedAllDay, $event->isAllDay());
        self::assertSame($expectedStart, $event->getStart()->format('Y-m-d H:i:s'));
        self::assertSame($expectedAllDay, $event->toArray()['allDay']);
        self::assertSame($withEnd ? '2024-01-18' : null, $event->getEnd()?->format('Y-m-d'));
    }

    /**
     * @return iterable<string, array{?bool, bool, bool, string}>
     */
    public static function provideAllDayCases(): iterable
    {
        yield 'null without an end is all-day' => [null, false, true, '2024-01-15 00:00:00'];
        yield 'null with an end is timed' => [null, true, false, '2024-01-15 14:30:00'];
        yield 'true with an end is a multi-day all-day event' => [true, true, true, '2024-01-15 00:00:00'];
        yield 'true without an end is all-day' => [true, false, true, '2024-01-15 00:00:00'];
        yield 'false without an end is timed' => [false, false, false, '2024-01-15 14:30:00'];
        yield 'false with an end is timed' => [false, true, false, '2024-01-15 14:30:00'];
    }

    public function testAMultiDayAllDayEventKeepsItsStartTimeWhenItBecomesTimed(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-18'), allDay: true);

        $event->setAllDay(false);

        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSubclassesOverridingTheSettersWithDateTimeStillGetDateTimes(): void
    {
        $event = new class('Title', new \DateTimeImmutable('2024-01-15 14:30:00'), new \DateTimeImmutable('2024-01-15 16:00:00')) extends Event {
            /** @var list<string> */
            public array $calls = [];

            public function setStart(\DateTime $start): Event
            {
                $this->calls[] = 'setStart '.$start->format('H:i');

                return parent::setStart($start);
            }

            public function setEnd(?\DateTime $end): Event
            {
                $this->calls[] = 'setEnd '.$end?->format('H:i');

                return parent::setEnd($end);
            }
        };

        self::assertSame(['setEnd 16:00', 'setStart 14:30'], $event->calls);
    }

    public function testSubclassesCanAlreadyWidenTheSetterParameters(): void
    {
        $event = new class('Title', new \DateTimeImmutable('2024-01-15 14:30:00')) extends Event {
            public function setStart(\DateTimeInterface $start): Event
            {
                return parent::setStart($start instanceof \DateTime ? $start : \DateTime::createFromInterface($start));
            }

            public function setEnd(?\DateTimeInterface $end): Event
            {
                return parent::setEnd(null === $end || $end instanceof \DateTime ? $end : \DateTime::createFromInterface($end));
            }
        };

        $event->setEnd(new \DateTimeImmutable('2024-01-15 16:00:00'));
        $event->setStart(new \DateTimeImmutable('2024-01-15 15:00:00'));

        self::assertSame('2024-01-15 15:00:00', $event->getStart()->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-15 16:00:00', $event->getEnd()?->format('Y-m-d H:i:s'));
    }
}
