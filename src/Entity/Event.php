<?php

declare(strict_types=1);

namespace CalendarBundle\Entity;

class Event
{
    protected bool $allDay = true;

    protected \DateTime $start;

    protected ?\DateTime $end;

    /**
     * The start with the time of day it had, kept while an all-day event
     * exposes it truncated to midnight, so the time comes back when the
     * event becomes timed.
     */
    private ?\DateTime $startWithTime = null;

    /**
     * An immutable date is copied into a \DateTime; a \DateTime is used as is.
     *
     * @param mixed[]   $options
     * @param bool|null $allDay  null makes the event all-day without an end and timed with one;
     *                           true with an end gives a multi-day all-day event
     */
    public function __construct(
        protected string $title,
        \DateTimeInterface $start,
        ?\DateTimeInterface $end = null,
        protected ?string $resourceId = null,
        protected array $options = [],
        ?bool $allDay = null,
    ) {
        $start = $start instanceof \DateTime ? $start : \DateTime::createFromInterface($start);
        // set before setEnd() runs, as the promoted property used to be
        $this->start = $start;
        $this->setEnd(null === $end || $end instanceof \DateTime ? $end : \DateTime::createFromInterface($end));
        if (null !== $allDay) {
            $this->allDay = $allDay;
        }
        $this->setStart($start);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    /**
     * In 9.0, $start will be typed \DateTimeInterface. A subclass that overrides
     * this method should declare its parameter as \DateTimeInterface: PHP already
     * accepts that wider type in an override, so it can be changed now.
     */
    public function setStart(\DateTime $start): self
    {
        $this->startWithTime = null;
        if ($this->allDay) {
            $this->startWithTime = clone $start;
            $start = clone $start;
            $start->setTime(0, 0, 0, 0);
        }
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    /**
     * Also resets allDay: true without an end, false with one.
     *
     * In 9.0, $end will be typed ?\DateTimeInterface. A subclass that overrides
     * this method should declare its parameter as ?\DateTimeInterface: PHP already
     * accepts that wider type in an override, so it can be changed now.
     */
    public function setEnd(?\DateTime $end): self
    {
        $this->allDay = null === $end;
        $this->end = $end;
        if (null !== $end) {
            $this->restoreStartTime();
        }

        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): self
    {
        $this->allDay = $allDay;
        if (!$allDay) {
            $this->restoreStartTime();
        } else {
            if ('00:00:00.000000' !== $this->start->format('H:i:s.u')) {
                $this->startWithTime = clone $this->start;
            }
            $this->start = clone $this->start;
            $this->start->setTime(0, 0, 0, 0);
        }

        return $this;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function setResourceId(?string $resourceId): self
    {
        $this->resourceId = $resourceId;

        return $this;
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
    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function getOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    public function addOption(string $name, mixed $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function removeOption(string $name): mixed
    {
        if (!isset($this->options[$name])) {
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
            'title' => $this->title,
            'start' => $this->start->format(\DateTime::ATOM),
            'allDay' => $this->allDay,
        ];

        if (null !== $this->end) {
            $event['end'] = $this->end->format(\DateTime::ATOM);
        }

        if (null !== $this->resourceId) {
            $event['resourceId'] = $this->resourceId;
        }

        return $event + $this->options;
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
