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
        self::assertNull($this->entity->getOption('non-existent-key'));

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

    public function testSetStartDoesNotMutateOriginalDateTimeForAllDayEvent(): void
    {
        $originalTime = '14:30:00';
        $date = new \DateTime('2024-01-15 ' . $originalTime);

        $event = new Event('All day event', $date);

        self::assertTrue($event->isAllDay());
        self::assertSame($originalTime, $date->format('H:i:s'), 'Original DateTime should not be mutated');
        self::assertSame('00:00:00', $event->getStart()?->format('H:i:s'), 'Event start time should be zeroed');
    }

    public function testSetEndNullResetsAllDayToTrue(): void
    {
        $event = new Event(
            'Meeting',
            new \DateTime('2024-01-15 14:00:00'),
            new \DateTime('2024-01-15 15:00:00'),
        );

        self::assertFalse($event->isAllDay());

        $event->setEnd(null);

        self::assertTrue($event->isAllDay());
    }

    public function testSetAllDayTrueNormalizesStartTime(): void
    {
        $event = new Event(
            'Meeting',
            new \DateTime('2024-01-15 14:30:00'),
            new \DateTime('2024-01-15 15:30:00'),
        );

        self::assertFalse($event->isAllDay());
        $start = $event->getStart();
        self::assertNotNull($start);
        self::assertSame('14:30:00', $start->format('H:i:s'));

        $event->setAllDay(true);

        self::assertTrue($event->isAllDay());
        $start = $event->getStart();
        self::assertNotNull($start);
        self::assertSame('00:00:00', $start->format('H:i:s'));
    }

    public function testFluentInterface(): void
    {
        $event = new Event('Initial', new \DateTime('2024-01-01'));

        $result = $event
            ->setTitle('Updated')
            ->setResourceId('resource-1')
            ->addOption('color', 'blue')
            ->addOption('url', 'https://example.com');

        self::assertSame($event, $result);
        self::assertSame('Updated', $event->getTitle());
        self::assertSame('resource-1', $event->getResourceId());
        self::assertSame('blue', $event->getOption('color'));
        self::assertSame('https://example.com', $event->getOption('url'));
    }
}
