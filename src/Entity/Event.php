<?php

declare(strict_types=1);

namespace CalendarBundle\Entity;

class Event
{
    protected bool $allDay = true;
    protected \DateTime $start;
    protected ?\DateTime $end;

    /**
     * The start as it was given, with its time of day. $start is this same
     * instance for a timed event, and a copy truncated to midnight for an
     * all-day one, so the time comes back when the event stops being all-day.
     * Null until setStart() runs.
     */
    protected ?\DateTime $timedStart = null;

    /**
     * @param mixed[]   $options
     * @param bool|null $allDay  null makes the event all-day when it has no end;
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
        // Set before setEnd() runs, as the promoted property used to be.
        $this->start = $this->timedStart = self::toMutable($start);
        $this->setEnd($end);
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

    public function setStart(\DateTimeInterface $start): self
    {
        $this->timedStart = self::toMutable($start);
        $this->start = $this->timedStart;
        $this->refreshStart(false);

        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    /**
     * Also resets allDay: true without an end, false with one. For a
     * multi-day all-day event, call setAllDay(true) after setEnd().
     */
    public function setEnd(?\DateTimeInterface $end): self
    {
        $wasAllDay = $this->allDay;
        $this->allDay = null === $end;
        $this->end = null === $end ? null : self::toMutable($end);
        $this->refreshStart($wasAllDay);

        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): self
    {
        $wasAllDay = $this->allDay;
        $this->allDay = $allDay;
        $this->refreshStart($wasAllDay);

        return $this;
    }

    /**
     * Sets the effective start from the all-day state: midnight while the
     * event is all-day, the start with its time of day once it is not.
     */
    private function refreshStart(bool $wasAllDay): void
    {
        if ($this->allDay) {
            $this->start = clone $this->start;
            $this->start->setTime(0, 0, 0, 0);

            return;
        }

        if (!$wasAllDay || null === $this->timedStart) {
            return;
        }

        // A time set in place on the all-day start wins over the remembered one.
        if ('00:00:00.000000' !== $this->start->format('H:i:s.u')) {
            $this->timedStart = $this->start;

            return;
        }

        // Keep the day of the all-day start, in case it was moved in place.
        $day = $this->start;
        if ($day->format('Y-m-d') !== $this->timedStart->format('Y-m-d')) {
            $this->timedStart = clone $this->timedStart;
            $this->timedStart->setDate((int) $day->format('Y'), (int) $day->format('n'), (int) $day->format('j'));
        }
        $this->start = $this->timedStart;
    }

    /**
     * Keeps a mutable \DateTime as is (same instance) and copies anything else into one.
     */
    private static function toMutable(\DateTimeInterface $date): \DateTime
    {
        return $date instanceof \DateTime ? $date : \DateTime::createFromInterface($date);
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
}
