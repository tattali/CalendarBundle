<?php

namespace Tests\CalendarBundle\Event;

use PhpSpec\ObjectBehavior;
use CalendarBundle\Event\CalendarEvent;
use CalendarBundle\Entity\Event;

class CalendarEventSpec extends ObjectBehavior
{
    private $start;
    private $end;
    private $filters;

    public function let()
    {
        $this->start = new \DateTime('2019-03-18 08:41:31');
        $this->end = new \DateTime('2019-03-18 08:41:31');
        $this->filters = [];

        $this->beAnInstanceOf(CalendarEvent::class);
        $this->beConstructedWith($this->start, $this->end, $this->filters);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CalendarEvent::class);
    }

    public function it_has_require_values()
    {
        $this->getStart()->shouldReturn($this->start);
        $this->getEnd()->shouldReturn($this->end);
        $this->getFilters()->shouldReturn($this->filters);
    }

    public function it_handle_events(
        Event $event
    ) {
        $this->addEvent($event);
        $this->getEvents()->shouldReturn([$event]);
    }
}
