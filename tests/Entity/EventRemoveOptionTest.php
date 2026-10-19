<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Entity;

use CalendarBundle\Entity\Event;
use PHPUnit\Framework\TestCase;

final class EventRemoveOptionTest extends TestCase
{
    public function testAnOptionWhoseValueIsNullCanBeRemoved(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15'), options: ['url' => null, 'color' => 'blue']);

        self::assertNull($event->removeOption('url'));
        self::assertSame(['color' => 'blue'], $event->getOptions());
        self::assertArrayNotHasKey('url', $event->toArray());
    }

    public function testRemovingAnOptionReturnsItsValue(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15'), options: ['color' => 'blue']);

        self::assertSame('blue', $event->removeOption('color'));
        self::assertSame([], $event->getOptions());
    }

    public function testRemovingAMissingOptionChangesNothing(): void
    {
        $event = new Event('Title', new \DateTime('2024-01-15'), options: ['color' => 'blue']);

        self::assertNull($event->removeOption('url'));
        self::assertSame(['color' => 'blue'], $event->getOptions());
    }
}
