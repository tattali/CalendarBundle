<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Event;

use CalendarBundle\Event\SetDataEvent;
use PHPUnit\Framework\TestCase;

final class SetDataEventTimeZoneTest extends TestCase
{
    public function testItExposesTheRequestedTimeZone(): void
    {
        $timeZone = new \DateTimeZone('America/New_York');

        $event = new SetDataEvent(
            new \DateTime('2026-10-01', $timeZone),
            new \DateTime('2026-11-01', $timeZone),
            [],
            $timeZone,
        );

        self::assertSame($timeZone, $event->getTimeZone());
    }

    public function testTimeZoneDefaultsToNull(): void
    {
        $event = new SetDataEvent(
            new \DateTime('2026-10-01'),
            new \DateTime('2026-11-01'),
            [],
        );

        self::assertNull($event->getTimeZone());
    }
}
