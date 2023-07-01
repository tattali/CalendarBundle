<?php

declare(strict_types=1);

namespace CalendarBundle\Event;

use CalendarBundle\Entity\Event;
use DateTimeInterface;

/**
 * This event is triggered before the serialization of the events.
 *
 * This event allows you to fill the calendar with your data.
 */
class CalendarEvent
{
    /**
     * @var Event[]
     */
    protected array $events = [];

    public function __construct(
        protected DateTimeInterface $start,
        protected DateTimeInterface $end,
        protected array $filters
    ) {
    }

    public function getStart(): DateTimeInterface
    {
        return $this->start;
    }

    public function getEnd(): DateTimeInterface
    {
        return $this->end;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addEvent(Event $event, bool $checkUnique=false): self
    {
        if (!$checkUnique || !\in_array($event, $this->events, true)) {
            $this->events[] = $event;
        }

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
