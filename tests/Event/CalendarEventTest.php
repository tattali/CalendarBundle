<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Event;

use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CalendarEventTest extends TestCase
{
    private \DateTime $start;
    private \DateTime $end;
    private array $filters;
    /** @var Event&MockObject $eventEntity */
    private $eventEntity;
    /** @var Event&MockObject $eventEntity2 */
    private $eventEntity2;
    private CalendarEvent $event;

    public function setUp(): void
    {
        $this->start = new \DateTime('2019-03-18 08:41:31');
        $this->end = new \DateTime('2019-03-18 08:41:31');
        $this->filters = [];

        $this->eventEntity = $this->createMock(Event::class);
        $this->eventEntity2 = $this->createMock(Event::class);

        $this->event = new CalendarEvent(
            $this->start,
            $this->end,
            $this->filters
        );
    }

    public function testItHasRequireValues(): void
    {
        $this->assertEquals($this->start, $this->event->getStart());
        $this->assertEquals($this->end, $this->event->getEnd());
        $this->assertEquals($this->filters, $this->event->getFilters());
    }

    public function testItHandleEvents(): void
    {
        $this->assertCount(0, $this->event->getEvents());

        $this->event->addEvent($this->eventEntity);
        $this->assertEquals([$this->eventEntity], $this->event->getEvents());
        $this->assertCount(1, $this->event->getEvents());

        $this->event->addEvent($this->eventEntity, true);
        $this->assertCount(1, $this->event->getEvents());

        $this->event->addEvent($this->eventEntity);
        $this->assertCount(2, $this->event->getEvents());

        $this->event->addEvent($this->eventEntity2, true);
        $this->assertCount(3, $this->event->getEvents());
    }
}
