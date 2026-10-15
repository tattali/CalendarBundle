<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Entity;

use CalendarBundle\Entity\Event;
use PHPUnit\Framework\TestCase;

final class EventDatesTest extends TestCase
{
    private const string FORMAT = 'Y-m-d H:i:s.u e';

    public function testConstructorAcceptsImmutableStartAndEnd(): void
    {
        $start = new \DateTimeImmutable('2024-01-15 14:30:45.123456', new \DateTimeZone('Europe/Paris'));
        $end = new \DateTimeImmutable('2024-01-15 16:00:00', new \DateTimeZone('America/New_York'));

        $event = new Event('Meeting', $start, $end);

        self::assertFalse($event->isAllDay());
        self::assertInstanceOf(\DateTime::class, $event->getStart());
        self::assertSame('2024-01-15 14:30:45.123456 Europe/Paris', $event->getStart()->format(self::FORMAT));
        self::assertInstanceOf(\DateTime::class, $event->getEnd());
        self::assertSame('2024-01-15 16:00:00.000000 America/New_York', $event->getEnd()->format(self::FORMAT));
    }

    public function testConstructorAcceptsImmutableStartForAllDayEvent(): void
    {
        $start = new \DateTimeImmutable('2024-01-15 14:30:00', new \DateTimeZone('Asia/Tokyo'));

        $event = new Event('Holiday', $start);

        self::assertTrue($event->isAllDay());
        self::assertNull($event->getEnd());
        self::assertSame('2024-01-15 00:00:00.000000 Asia/Tokyo', $event->getStart()->format(self::FORMAT));
    }

    public function testSettersAcceptImmutableDates(): void
    {
        $event = new Event('Meeting', new \DateTime('2024-01-01 09:00:00'), new \DateTime('2024-01-01 10:00:00'));

        $event
            ->setStart(new \DateTimeImmutable('2024-02-20 11:15:30', new \DateTimeZone('Europe/London')))
            ->setEnd(new \DateTimeImmutable('2024-02-20 12:45:00', new \DateTimeZone('Europe/London')))
        ;

        self::assertFalse($event->isAllDay());
        self::assertSame('2024-02-20 11:15:30.000000 Europe/London', $event->getStart()->format(self::FORMAT));
        self::assertNotNull($event->getEnd());
        self::assertSame('2024-02-20 12:45:00.000000 Europe/London', $event->getEnd()->format(self::FORMAT));
    }

    public function testSetStartAcceptsImmutableForAllDayEvent(): void
    {
        $event = new Event('Holiday', new \DateTime('2024-01-01'));

        $event->setStart(new \DateTimeImmutable('2024-03-10 18:00:00', new \DateTimeZone('UTC')));

        self::assertTrue($event->isAllDay());
        self::assertSame('2024-03-10 00:00:00.000000 UTC', $event->getStart()->format(self::FORMAT));
    }

    public function testImmutableDatesPassedInAreUntouchedByLaterChanges(): void
    {
        $start = new \DateTimeImmutable('2024-01-15 14:30:00', new \DateTimeZone('UTC'));
        $end = new \DateTimeImmutable('2024-01-15 15:30:00', new \DateTimeZone('UTC'));

        $event = new Event('Meeting', $start, $end);
        $event->getStart()->modify('+1 day');
        self::assertNotNull($event->getEnd());
        $event->getEnd()->modify('+1 day');
        $event->setAllDay(true);

        self::assertSame('2024-01-15 14:30:00.000000 UTC', $start->format(self::FORMAT));
        self::assertSame('2024-01-15 15:30:00.000000 UTC', $end->format(self::FORMAT));
    }

    public function testMutableDatesKeepTheirIdentityForTimedEvents(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $end = new \DateTime('2024-01-15 15:30:00');

        $event = new Event('Meeting', $start, $end);

        self::assertSame($start, $event->getStart());
        self::assertSame($end, $event->getEnd());

        $newStart = new \DateTime('2024-01-16 09:00:00');
        $newEnd = new \DateTime('2024-01-16 10:00:00');
        $event->setEnd($newEnd)->setStart($newStart);

        self::assertSame($newStart, $event->getStart());
        self::assertSame($newEnd, $event->getEnd());
    }

    public function testMutableStartIsNotMutatedForAllDayEvent(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $event = new Event('Holiday', new \DateTime('2024-01-01'));

        $event->setStart($start);

        self::assertNotSame($start, $event->getStart());
        self::assertSame('2024-01-15 14:30:00', $start->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-15 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testToArrayIsTheSameForMutableAndImmutableDates(): void
    {
        $zone = new \DateTimeZone('Europe/Paris');
        $mutable = new Event('Meeting', new \DateTime('2024-01-15 14:30:00', $zone), new \DateTime('2024-01-15 15:30:00', $zone));
        $immutable = new Event('Meeting', new \DateTimeImmutable('2024-01-15 14:30:00', $zone), new \DateTimeImmutable('2024-01-15 15:30:00', $zone));

        self::assertSame(
            [
                'title' => 'Meeting',
                'start' => '2024-01-15T14:30:00+01:00',
                'allDay' => false,
                'end' => '2024-01-15T15:30:00+01:00',
            ],
            $mutable->toArray(),
        );
        self::assertSame($mutable->toArray(), $immutable->toArray());
    }

    public function testToArrayOfAnAllDayEventIsUnchanged(): void
    {
        $event = new Event('Holiday', new \DateTimeImmutable('2024-01-15 14:30:00', new \DateTimeZone('Europe/Paris')), null, 'room-1', ['color' => 'red']);

        $array = $event->toArray();

        // Only the date is checked: how an all-day date is formatted is not this test's concern.
        self::assertSame(['title', 'start', 'allDay', 'resourceId', 'color'], array_keys($array));
        self::assertSame('Holiday', $array['title']);
        self::assertIsString($array['start']);
        self::assertStringStartsWith('2024-01-15', $array['start']);
        self::assertTrue($array['allDay']);
        self::assertSame('room-1', $array['resourceId']);
        self::assertSame('red', $array['color']);
    }

    public function testASubclassSetEndCanReadTheStartDuringConstruction(): void
    {
        $event = new class ('Meeting', new \DateTimeImmutable('2024-01-15 14:30:00'), new \DateTimeImmutable('2024-01-15 16:00:00')) extends Event {
            public ?string $startSeenBySetEnd = null;

            public function setEnd(?\DateTimeInterface $end): Event
            {
                $this->startSeenBySetEnd = $this->start->format('Y-m-d H:i:s');

                return parent::setEnd($end);
            }
        };

        self::assertSame('2024-01-15 14:30:00', $event->startSeenBySetEnd);
    }

    public function testSetAllDayFalseRestoresTheOriginalStartTime(): void
    {
        $event = new Event('Holiday', new \DateTime('2024-01-15 14:30:00'));
        self::assertSame('00:00:00', $event->getStart()->format('H:i:s'));

        $event->setAllDay(false);

        self::assertFalse($event->isAllDay());
        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetAllDayFalseRestoresTheTimeAndZoneOfAnImmutableStart(): void
    {
        $event = new Event('Holiday', new \DateTimeImmutable('2024-01-15 14:30:45.5', new \DateTimeZone('Europe/Paris')));

        $event->setAllDay(false);

        self::assertSame('2024-01-15 14:30:45.500000 Europe/Paris', $event->getStart()->format(self::FORMAT));
    }

    public function testSetAllDayFalseGivesBackTheCallersMutableStart(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $event = new Event('Holiday', $start);

        $event->setAllDay(false);

        self::assertSame($start, $event->getStart());
        self::assertSame('2024-01-15 14:30:00', $start->format('Y-m-d H:i:s'));
    }

    public function testSetEndOnAnAllDayEventRestoresTheStartTime(): void
    {
        $event = new Event('Holiday', new \DateTime('2024-01-15 14:30:00'));

        $event->setEnd(new \DateTime('2024-01-15 16:00:00'));

        self::assertFalse($event->isAllDay());
        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetEndNullTruncatesTheStartAgain(): void
    {
        $event = new Event('Meeting', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-15 16:00:00'));

        $event->setEnd(null);

        self::assertTrue($event->isAllDay());
        self::assertSame('2024-01-15 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));

        $event->setEnd(new \DateTime('2024-01-15 17:00:00'));

        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testSetStartOnAnAllDayEventKeepsTheNewTimeForLater(): void
    {
        $event = new Event('Holiday', new \DateTime('2024-01-15 14:30:00'));

        $event->setStart(new \DateTimeImmutable('2024-02-01 09:15:00'));
        self::assertSame('2024-02-01 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));

        $event->setAllDay(false);

        self::assertSame('2024-02-01 09:15:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testTimedToAllDayAndBackKeepsTheSameMutableStart(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $event = new Event('Meeting', $start, new \DateTime('2024-01-15 16:00:00'));

        $event->setAllDay(true);
        $allDayStart = $event->getStart()->format('Y-m-d H:i:s');
        $event->setAllDay(true);
        self::assertSame('2024-01-15 00:00:00', $allDayStart);
        self::assertSame('2024-01-15 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));
        $event->setAllDay(false);

        self::assertSame($start, $event->getStart());
        self::assertSame('2024-01-15 14:30:00', $start->format('Y-m-d H:i:s'));
    }

    public function testSetAllDayFalseOnATimedEventChangesNothing(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $event = new Event('Meeting', $start, new \DateTime('2024-01-15 16:00:00'));

        $event->setAllDay(false);

        self::assertSame($start, $event->getStart());
        self::assertSame('2024-01-15 14:30:00', $start->format('Y-m-d H:i:s'));
    }

    public function testAnAllDayStartMovedInPlaceKeepsItsNewDayWhenTheTimeIsRestored(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');
        $event = new Event('Holiday', $start);

        $event->getStart()->modify('+2 days');
        $event->setAllDay(true);
        self::assertSame('2024-01-17 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));

        $event->setAllDay(false);

        self::assertSame('2024-01-17 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-15 14:30:00', $start->format('Y-m-d H:i:s'), 'The caller\'s DateTime must not be mutated');
    }

    public function testATimedStartModifiedInPlaceKeepsTheChangeAcrossAllDay(): void
    {
        $event = new Event('Meeting', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-15 16:00:00'));

        $event->getStart()->modify('+1 hour');
        $event->setAllDay(true)->setAllDay(false);

        self::assertSame('2024-01-15 15:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testATimeSetInPlaceOnAnAllDayStartIsKeptBySetEnd(): void
    {
        $event = new Event('Dentist', new \DateTime('2024-01-15'));

        $event->getStart()->setTime(9, 0);
        $event->setEnd(new \DateTime('2024-01-15 10:00:00'));

        self::assertFalse($event->isAllDay());
        self::assertSame('2024-01-15 09:00:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testATimeSetInPlaceOnAnAllDayStartIsKeptBySetAllDayFalse(): void
    {
        $event = new Event('Dentist', new \DateTime('2024-01-15 10:00:00'));

        $event->getStart()->setTime(7, 0);
        $event->setAllDay(false);

        self::assertSame('2024-01-15 07:00:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testASubclassThatSetsTheStartItselfIsStillTruncated(): void
    {
        $event = new class extends Event {
            public function __construct()
            {
                $this->title = 'Custom';
                $this->start = new \DateTime('2024-01-15 14:30:00');
                $this->end = new \DateTime('2024-01-15 16:00:00');
                $this->allDay = false;
            }
        };

        $event->setAllDay(true);
        $allDayStart = $event->getStart()->format('Y-m-d H:i:s');
        $event->setAllDay(false);

        self::assertSame('2024-01-15 00:00:00', $allDayStart);
        self::assertSame('2024-01-15 00:00:00', $event->getStart()->format('Y-m-d H:i:s'), 'No time to restore when setStart() never ran');
    }

    public function testConstructorBuildsAMultiDayAllDayEvent(): void
    {
        $end = new \DateTime('2024-01-18');

        $event = new Event('Conference', new \DateTimeImmutable('2024-01-15 14:30:00', new \DateTimeZone('UTC')), $end, allDay: true);

        self::assertTrue($event->isAllDay());
        self::assertSame($end, $event->getEnd());
        self::assertSame('2024-01-15 00:00:00.000000 UTC', $event->getStart()->format(self::FORMAT));
        $array = $event->toArray();

        self::assertSame(['title', 'start', 'allDay', 'end'], array_keys($array));
        self::assertIsString($array['start']);
        self::assertStringStartsWith('2024-01-15', $array['start']);
        self::assertTrue($array['allDay']);
        self::assertIsString($array['end']);
        self::assertStringStartsWith('2024-01-18', $array['end']);
    }

    public function testAMultiDayAllDayEventGetsItsTimeBackWhenItStopsBeingAllDay(): void
    {
        $event = new Event('Conference', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-18 12:00:00'), null, [], true);
        $allDayStart = $event->getStart()->format('Y-m-d H:i:s');

        $event->setAllDay(false);

        self::assertSame('2024-01-15 00:00:00', $allDayStart);
        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testConstructorBuildsATimedEventWithoutAnEnd(): void
    {
        $start = new \DateTime('2024-01-15 14:30:00');

        $event = new Event('Reminder', $start, allDay: false);

        self::assertFalse($event->isAllDay());
        self::assertNull($event->getEnd());
        self::assertSame($start, $event->getStart());
        self::assertSame('2024-01-15 14:30:00', $event->getStart()->format('Y-m-d H:i:s'));
    }

    public function testConstructorStillInfersAllDayFromTheEndByDefault(): void
    {
        $withoutEnd = new Event('Holiday', new \DateTime('2024-01-15 14:30:00'), null, null, [], null);
        $withEnd = new Event('Meeting', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-15 16:00:00'), allDay: null);

        self::assertTrue($withoutEnd->isAllDay());
        self::assertSame('00:00:00', $withoutEnd->getStart()->format('H:i:s'));
        self::assertFalse($withEnd->isAllDay());
        self::assertSame('14:30:00', $withEnd->getStart()->format('H:i:s'));
    }

    public function testSetEndStillResetsAllDaySoSetAllDayMustComeAfterIt(): void
    {
        $event = new Event('Conference', new \DateTime('2024-01-15 14:30:00'), new \DateTime('2024-01-18'), allDay: true);

        $event->setEnd(new \DateTime('2024-01-19'));
        $timedAfterSetEnd = !$event->isAllDay();
        $event->setAllDay(true);

        self::assertTrue($timedAfterSetEnd);
        self::assertTrue($event->isAllDay());
        self::assertSame('2024-01-15 00:00:00', $event->getStart()->format('Y-m-d H:i:s'));
    }
}
