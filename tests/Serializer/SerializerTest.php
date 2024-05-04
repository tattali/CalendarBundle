<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Serializer;

use CalendarBundle\Entity\Event;
use CalendarBundle\Serializer\Serializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SerializerTest extends TestCase
{
    private Event&MockObject $eventEntity1;
    private Event&MockObject $eventEntity2;
    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->eventEntity1 = $this->createMock(Event::class);
        $this->eventEntity2 = $this->createMock(Event::class);

        $this->serializer = new Serializer();
    }

    public function testItSerializesDataSuccessfully(): void
    {
        $this->eventEntity1->method('toArray')->willReturn(
            [
                'title' => 'Event 1',
                'start' => '2015-01-20T11:50:00Z',
                'allDay' => false,
                'end' => '2015-01-21T11:50:00Z',
            ],
        );
        $this->eventEntity2->method('toArray')->willReturn(
            [
                'title' => 'Event 2',
                'start' => '2015-01-22T11:50:00Z',
                'allDay' => true,
            ],
        );

        $data = json_encode([
            [
                'title' => 'Event 1',
                'start' => '2015-01-20T11:50:00Z',
                'allDay' => false,
                'end' => '2015-01-21T11:50:00Z',
            ],
            [
                'title' => 'Event 2',
                'start' => '2015-01-22T11:50:00Z',
                'allDay' => true,
            ],
        ]);

        self::assertSame($data, $this->serializer->serialize([$this->eventEntity1, $this->eventEntity2]));
    }

    public function testSerializesShouldReturnEmtpyIfEventsAreEmpty(): void
    {
        self::assertSame('[]', $this->serializer->serialize([]));
    }
}
