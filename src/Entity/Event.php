<?php

declare(strict_types=1);

namespace CalendarBundle\Entity;

class Event
{
    protected bool $allDay = true;

    protected \DateTimeImmutable $start;

    protected ?\DateTimeImmutable $end;

    /**
     * The start with the time of day it had, kept while an all-day event
     * exposes it truncated to midnight, so the time comes back when the
     * event becomes timed.
     */
    private ?\DateTimeImmutable $startWithTime = null;

    /**
     * The dates are stored as \DateTimeImmutable: a \DateTime is copied, so
     * changing it later does not change the event. The setters still receive
     * a \DateTime, as in 8.2.
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
        $start = self::toDateTime($start);
        $end = null === $end ? null : self::toDateTime($end);
        // set before setEnd() runs, as the promoted properties used to be
        $this->start = self::toImmutable($start);
        $this->end = null === $end ? null : self::toImmutable($end);
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

    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * A \DateTime is copied into a \DateTimeImmutable; an immutable date is
     * stored as is.
     */
    public function setStart(\DateTimeInterface $start): self
    {
        $start = self::toImmutable($start);
        $this->startWithTime = null;
        if ($this->allDay) {
            $this->startWithTime = $start;
            $start = $start->setTime(0, 0, 0, 0);
        }
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * Also resets allDay: true without an end, false with one. A \DateTime
     * is copied into a \DateTimeImmutable; an immutable date is stored as is.
     */
    public function setEnd(?\DateTimeInterface $end): self
    {
        $end = null === $end ? null : self::toImmutable($end);
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
                $this->startWithTime = $this->start;
            }
            $this->start = $this->start->setTime(0, 0, 0, 0);
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
        $dateFormat = $this->allDay ? 'Y-m-d' : \DateTime::ATOM;

        $event = [
            'title' => $this->title,
            'start' => $this->start->format($dateFormat),
            'allDay' => $this->allDay,
        ];

        if (null !== $this->end) {
            $event['end'] = $this->end->format($dateFormat);
        }

        if (null !== $this->resourceId) {
            $event['resourceId'] = $this->resourceId;
        }

        return $event + $this->options;
    }

    private static function toDateTime(\DateTimeInterface $date): \DateTime
    {
        return $date instanceof \DateTime ? $date : \DateTime::createFromInterface($date);
    }

    private static function toImmutable(\DateTimeInterface $date): \DateTimeImmutable
    {
        return $date instanceof \DateTimeImmutable ? $date : \DateTimeImmutable::createFromInterface($date);
    }

    /**
     * Gives back the time of day an all-day start was truncated from, on the
     * day the start is on now (a subclass may have moved it). A time a
     * subclass set on the truncated start wins over the remembered one.
     */
    private function restoreStartTime(): void
    {
        $start = $this->startWithTime;
        $this->startWithTime = null;
        if (null === $start || '00:00:00.000000' !== $this->start->format('H:i:s.u')) {
            return;
        }

        $this->start = $start->setDate((int) $this->start->format('Y'), (int) $this->start->format('n'), (int) $this->start->format('j'));
    }
}
