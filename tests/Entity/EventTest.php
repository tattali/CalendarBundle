<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Entity;

use CalendarBundle\Entity\Event;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    private string $title;
    private \DateTime $start;
    private \DateTime $end;
    private string $resourceId;
    /** @var mixed[] */
    private array $options;
    private Event $entity;

    protected function setUp(): void
    {
        $this->title = 'Title';
        $this->start = new \DateTime('2019-03-18 08:41:31');
        $this->end = new \DateTime('2019-03-18 08:41:31');
        $this->resourceId = 'id';
        $this->options = ['textColor' => 'blue'];

        $this->entity = new Event(
            $this->title,
            $this->start,
            $this->end,
            $this->resourceId,
            $this->options,
        );
    }

    public function testItHasRequireValues(): void
    {
        self::assertSame($this->title, $this->entity->getTitle());
        self::assertSame($this->start, $this->entity->getStart());
        self::assertSame($this->end, $this->entity->getEnd());
        self::assertSame($this->resourceId, $this->entity->getResourceId());
        self::assertSame($this->options, $this->entity->getOptions());
    }

    public function testItShouldConvertItsValuesInToArray(): void
    {
        $optionName = 'url';
        $optionValue = 'www.url.com';

        $options = [
            $optionName => $optionValue,
        ];

        $allDay = false;

        $this->entity->setAllDay($allDay);

        $this->entity->addOption('be-removed', 'value');
        $this->entity->removeOption('be-removed');

        self::assertNull($this->entity->removeOption('no-found-key'));

        $this->entity->setOptions($options);
        self::assertSame($options, $this->entity->getOptions());

        self::assertSame($optionValue, $this->entity->getOption($optionName));

        self::assertSame(
            [
                'title' => $this->title,
                'start' => $this->start->format(\DateTime::ATOM),
                'allDay' => $allDay,
                'end' => $this->end->format(\DateTime::ATOM),
                'resourceId' => $this->resourceId,
                $optionName => $optionValue,
            ],
            $this->entity->toArray(),
        );
    }

    public function testItShouldSetAllDayPropertyAccordingly(): void
    {
        $event = new Event(
            $this->title,
            $this->start,
        );
        self::assertTrue($event->isAllDay());

        $event2 = new Event(
            $this->title,
            $this->start,
            $this->end,
        );
        self::assertFalse($event2->isAllDay());
    }

    public function testItSetPropertiesAccordingly(): void
    {
        $newValue = 'changed';

        $this->entity->setTitle($newValue);
        self::assertSame($newValue, $this->entity->getTitle());

        $this->entity->setResourceId($newValue);
        self::assertSame($newValue, $this->entity->getResourceId());
    }
}
