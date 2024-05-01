<?php

declare(strict_types=1);

namespace CalendarBundle\Event;

use CalendarBundle\Entity\Event;

/**
 * This event is triggered before the serialization of the events.
 *
 * This event allows you to fill the calendar with your data.
 */
class SetDataEvent
{
    /**
     * @var Event[]
     */
    private array $events;

    public function __construct(
        private readonly \DateTime $start,
        private readonly \DateTime $end,
        private readonly array $filters,
    ) {
        $this->events = [];
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function getEnd(): \DateTime
    {
        return $this->end;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addEvent(Event $event): self
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
