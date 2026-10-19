<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Event;

use CalendarBundle\Event\SetDataEvent;
use PHPUnit\Framework\TestCase;

final class SetDataEventImmutableDatesTest extends TestCase
{
    public function testItCopiesTheDateTimesItIsGiven(): void
    {
        $tokyo = new \DateTimeZone('Asia/Tokyo');
        $start = new \DateTime('2026-10-01 00:00:00.250000', $tokyo);
        $end = new \DateTime('2026-11-01 00:00:00', $tokyo);

        $event = new SetDataEvent($start, $end, []);
        $start->modify('+1 day');
        $end->setTime(12, 0);

        self::assertSame(\DateTimeImmutable::class, $event->getStart()::class);
        self::assertSame(\DateTimeImmutable::class, $event->getEnd()::class);
        self::assertSame('2026-10-01 00:00:00.250000 Asia/Tokyo', $event->getStart()->format('Y-m-d H:i:s.u e'));
        self::assertSame('2026-11-01 00:00:00 Asia/Tokyo', $event->getEnd()->format('Y-m-d H:i:s e'));
    }

    public function testItKeepsTheImmutableDatesItIsGiven(): void
    {
        $start = new \DateTimeImmutable('2026-10-01');
        $end = new \DateTimeImmutable('2026-11-01');

        $event = new SetDataEvent($start, $end, []);

        self::assertSame($start, $event->getStart());
        self::assertSame($end, $event->getEnd());
    }
}
