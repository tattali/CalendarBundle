<?php

declare(strict_types=1);

namespace CalendarBundle\Event;

use CalendarBundle\Entity\Event;
use DateTimeInterface;
use Symfony\Component\EventDispatcher\Event as BaseEvent;

class CalendarEvent extends BaseEvent
{
    /**
     * @var DateTimeInterface
     */
    protected $start;

    /**
     * @var DateTimeInterface
     */
    protected $end;

    /**
     * @var array
     */
    protected $filters;

    /**
     * @var Event[]
     */
    protected $events = [];

    /**
     * @var string|null
     */
    private $timezone;

    public function __construct(
        DateTimeInterface $start,
        DateTimeInterface $end,
        array $filters,
        ?string $timezone = null
    ) {
        $this->start = $start;
        $this->end = $end;
        $this->filters = $filters;
        $this->timezone = $timezone;
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

    public function addEvent(Event $event): self
    {
        if (!\in_array($event, $this->events, true)) {
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

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }
}
