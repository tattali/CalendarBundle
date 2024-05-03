<?php

declare(strict_types=1);

namespace CalendarBundle\Entity;

class Event
{
    protected bool $allDay = true;

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
        if ($this->allDay) {
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
        if ($end) {
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
        return $this->options[$name];
    }

    public function addOption(string $name, mixed $value): void
    {
        $this->options[$name] = $value;
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
            'title' => $this->getTitle(),
            'start' => $this->getStart()?->format(\DateTime::ATOM),
            'allDay' => $this->isAllDay(),
        ];

        if ($this->getEnd()) {
            $event['end'] = $this->getEnd()->format(\DateTime::ATOM);
        }

        if ($this->getResourceId()) {
            $event['resourceId'] = $this->getResourceId();
        }

        return [...$event, ...$this->getOptions()];
    }
}
