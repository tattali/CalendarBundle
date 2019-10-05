<?php

declare(strict_types=1);

namespace CalendarBundle\Entity;

use DateTimeInterface;

class Event
{
    // 2018-09-01T12:30:00+XX:XX
    public const DATE_FORMAT_ISO_8601_WITH_OFFSET = 'Y-m-d\\TH:i:sP';
    // 2018-09-01T12:30:00
    public const DATE_FORMAT_ISO_8601_WITHOUT_OFFSET = 'Y-m-d\\TH:i:s';
    // 2018-09-01T12:30:00Z
    public const DATE_FORMAT_ISO_8601_UTC = 'Y-m-d\\TH:i:s\\Z';
    // backward compatibility
    public const DATE_FORMAT = self::DATE_FORMAT_ISO_8601_UTC;

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
     * @var string
     */
    protected $dateFormat;

    public function __construct(
        string $title,
        DateTimeInterface $start,
        ?DateTimeInterface $end = null,
        array $options = [],
        string $dateFormat = self::DATE_FORMAT_ISO_8601_UTC
    ) {
        $this->setTitle($title);
        $this->setStart($start);
        $this->setEnd($end);
        $this->setOptions($options);
        $this->setDateFormat($dateFormat);
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

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @param string|int $name
     */
    public function getOption($name)
    {
        return $this->options[$name];
    }

    /**
     * @param string|int $name
     */
    public function addOption($name, $value): void
    {
        $this->options[$name] = $value;
    }

    /**
     * @param string|int $name
     *
     * @return mixed|null
     */
    public function removeOption($name)
    {
        if (!isset($this->options[$name]) && !\array_key_exists($name, $this->options)) {
            return null;
        }

        $removed = $this->options[$name];
        unset($this->options[$name]);

        return $removed;
    }

    /**
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * @param string $dateFormat
     */
    public function setDateFormat(string $dateFormat): void
    {
        $this->dateFormat = $dateFormat;
    }

    public function toArray(): array
    {
        $event = [
            'title' => $this->getTitle(),
            'start' => $this->getStart()->format($this->getDateFormat()),
            'allDay' => $this->isAllDay(),
        ];

        if (null !== $this->getEnd()) {
            $event['end'] = $this->getEnd()->format($this->getDateFormat());
        }

        return array_merge($event, $this->getOptions());
    }
}
