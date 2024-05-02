<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Event;

use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SetDataEventTest extends TestCase
{
    private \DateTime $start;
    private \DateTime $end;
    private array $filters;
    /** @var Event&MockObject */
    private $eventEntity;
    /** @var Event&MockObject */
    private $eventEntity2;
    private SetDataEvent $event;

    protected function setUp(): void
    {
        $this->start = new \DateTime('2019-03-18 08:41:31');
        $this->end = new \DateTime('2019-03-18 08:41:31');
        $this->filters = [];

        $this->eventEntity = $this->createMock(Event::class);
        $this->eventEntity2 = $this->createMock(Event::class);

        $this->event = new SetDataEvent(
            $this->start,
            $this->end,
            $this->filters,
        );
    }

    public function testItHasRequireValues(): void
    {
        self::assertSame($this->start, $this->event->getStart());
        self::assertSame($this->end, $this->event->getEnd());
        self::assertSame($this->filters, $this->event->getFilters());
    }

    public function testItHandleEvents(): void
    {
        self::assertCount(0, $this->event->getEvents());

        $this->event->addEvent($this->eventEntity);
        self::assertSame([$this->eventEntity], $this->event->getEvents());
        self::assertCount(1, $this->event->getEvents());

        $this->event->addEvent($this->eventEntity);
        self::assertCount(2, $this->event->getEvents());

        $this->event->addEvent($this->eventEntity2);
        self::assertCount(3, $this->event->getEvents());
    }
}
