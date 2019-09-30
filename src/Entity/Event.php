<?php

declare(strict_types=1);

namespace CalendarBundle\Entity;

use DateTimeInterface;

class Event
{
    const DATE_FORMAT = 'Y-m-d\\TH:i:s.u\\Z';

    /**
     * @var string
     */
    protected $title;

    /**
     * @var DateTimeInterface
     */
    protected $start;

    /**
     * @var DateTimeInterface|null
     */
    protected $end;

    /**
     * @var bool
     */
    protected $allDay = true;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param string $title
     * @param DateTimeInterface $start
     * @param DateTimeInterface|null $end
     * @param array $options
     */
    public function __construct(
        string $title,
        DateTimeInterface $start,
        ?DateTimeInterface $end = null,
        array $options = []
    ) {
        $this->setTitle($title);
        $this->setStart($start);
        $this->setEnd($end);
        $this->setOptions($options);
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getStart(): ?DateTimeInterface
    {
        return $this->start;
    }

    /**
     * @param DateTimeInterface $start
     */
    public function setStart(DateTimeInterface $start): void
    {
        $this->start = $start;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    /**
     * @param DateTimeInterface|null $end
     */
    public function setEnd(?DateTimeInterface $end): void
    {
        if (null !== $end) {
            $this->allDay = false;
        }
        $this->end = $end;
    }

    /**
     * @return bool
     */
    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    /**
     * @param bool $allDay
     */
    public function setAllDay(bool $allDay): void
    {
        $this->allDay = $allDay;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption(string $name)
    {
        return $this->options[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function addOption(string $name, $value): void
    {
        $this->options[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function removeOption(string $name)
    {
        if (!isset($this->options[$name]) && !\array_key_exists($name, $this->options)) {
            return null;
        }

        $removed = $this->options[$name];
        unset($this->options[$name]);

        return $removed;
    }

    /**
     * @return array
     */
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

        return array_merge($event, $this->getOptions());
    }
}
