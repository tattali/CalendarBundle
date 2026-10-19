<?php

declare(strict_types=1);

namespace CalendarBundle\Entity;

class Event
{
    protected bool $allDay = true;

    /**
     * The start with the time of day it was given, kept while an all-day
     * event exposes it truncated to midnight, so the time comes back when
     * the event becomes timed.
     */
    private ?\DateTime $startWithTime = null;

    /**
     * @param mixed[] $options
     */
    public function __construct(
        protected string $title,
        protected \DateTime $start,
        protected ?\DateTime $end = null,
        protected ?string $resourceId = null,
        protected array $options = [],
    ) {
        $this->setEnd($end);
        $this->setStart($start);
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getStart(): ?\DateTime
    {
        return $this->start;
    }

    public function setStart(\DateTime $start): void
    {
        $this->startWithTime = null;
        if ($this->allDay) {
            $this->startWithTime = clone $start;
            $start = clone $start;
            $start->setTime(0, 0, 0, 0);
        }
        $this->start = $start;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    public function setEnd(?\DateTime $end): void
    {
        if (null !== $end) {
            $this->allDay = false;
            $this->restoreStartTime();
        }
        $this->end = $end;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): void
    {
        $this->allDay = $allDay;
        if (!$allDay) {
            $this->restoreStartTime();
        }
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function setResourceId(?string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    /**
     * @return mixed[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param mixed[] $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    public function addOption(string $name, mixed $value): void
    {
        $this->options[$name] = $value;
    }

    public function removeOption(string $name): mixed
    {
        if (!\array_key_exists($name, $this->options)) {
            return null;
        }

        $removed = $this->options[$name];
        unset($this->options[$name]);

        return $removed;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $event = [
            'title' => $this->getTitle(),
            'start' => $this->getStart()?->format(\DateTime::ATOM),
            'allDay' => $this->isAllDay(),
        ];

        if (null !== $this->getEnd()) {
            $event['end'] = $this->getEnd()->format(\DateTime::ATOM);
        }

        if (!\in_array($this->getResourceId(), [null, '', '0'], true)) {
            $event['resourceId'] = $this->getResourceId();
        }

        return [...$event, ...$this->getOptions()];
    }

    /**
     * Gives back the time of day an all-day start was truncated from, on the
     * day the start is on now (it may have been moved in place). A time set in
     * place on the truncated start wins over the remembered one.
     */
    private function restoreStartTime(): void
    {
        $start = $this->startWithTime;
        $this->startWithTime = null;
        if (null === $start || '00:00:00.000000' !== $this->start->format('H:i:s.u')) {
            return;
        }

        $start->setDate((int) $this->start->format('Y'), (int) $this->start->format('n'), (int) $this->start->format('j'));
        $this->start = $start;
    }
}
