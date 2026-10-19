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

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    /**
     * A \DateTime is copied into a \DateTimeImmutable; an immutable date is
     * stored as is.
     *
     * @param mixed[] $filters
     */
    public function __construct(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        private readonly array $filters,
        private readonly ?\DateTimeZone $timeZone = null,
    ) {
        $this->start = $start instanceof \DateTimeImmutable ? $start : \DateTimeImmutable::createFromInterface($start);
        $this->end = $end instanceof \DateTimeImmutable ? $end : \DateTimeImmutable::createFromInterface($end);
        $this->events = [];
    }

    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): \DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * @return mixed[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getTimeZone(): ?\DateTimeZone
    {
        return $this->timeZone;
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

    public function getEventCount(): int
    {
        return \count($this->events);
    }
}
