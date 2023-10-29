<?php

declare(strict_types=1);

namespace CalendarBundle\Entity;

use DateTimeInterface;

class Event
{
    public const DATE_FORMAT = 'Y-m-d\\TH:i:s.u\\Z';

    protected bool $allDay = true;

    public function __construct(
        protected string $title,
        protected DateTimeInterface $start,
        protected ?DateTimeInterface $end = null,
        protected ?string $resourceId = null,
        protected array $options = []
    ) {
        $this->setEnd($this->end);
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getStart(): ?DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(DateTimeInterface $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(?DateTimeInterface $end): void
    {
        if (null !== $end) {
            $this->allDay = false;
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
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function setResourceId(?string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getOption(int|string $name)
    {
        return $this->options[$name];
    }

    public function addOption(int|string $name, $value): void
    {
        $this->options[$name] = $value;
    }

    public function removeOption(int|string $name): mixed
    {
        if (!isset($this->options[$name]) && !\array_key_exists($name, $this->options)) {
            return null;
        }

        $removed = $this->options[$name];
        unset($this->options[$name]);

        return $removed;
    }

    public function toArray(): array
    {
        $event = [
            'title' => $this->getTitle(),
            'start' => $this->getStart()->format(self::DATE_FORMAT),
            'allDay' => $this->isAllDay(),
        ];

        if (null !== $this->getEnd()) {
            $event['end'] = $this->getEnd()->format(self::DATE_FORMAT);
        }

        if (null !== $this->getResourceId()) {
            $event['resourceId'] = $this->getResourceId();
        }

        $toReturn = array_merge($event, $this->getOptions());

        if (isset($toReturn['allDay']) && $toReturn['allDay']) {
            $toReturn['start'] = $this->getStart()->format('Y-m-d');
        }

        return $toReturn;
    }
}
