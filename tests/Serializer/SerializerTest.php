<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Serializer;

use CalendarBundle\Entity\Event;
use CalendarBundle\Serializer\Serializer;
use PHPUnit\Framework\TestCase;

class SerializerTest extends TestCase
{
    private $eventEntity1;
    private $eventEntity2;
    private $serializer;

    public function setUp(): void
    {
        $this->eventEntity1 = $this->createMock(Event::class);
        $this->eventEntity2 = $this->createMock(Event::class);

        $this->serializer = new Serializer();
    }

    public function testItSerializesDataSuccessfully()
    {
        $this->eventEntity1->method('toArray')->willReturn(
            [
                'title' => 'Event 1',
                'start' => '2015-01-20T11:50:00Z',
                'allDay' => false,
                'end' => '2015-01-21T11:50:00Z',
            ]
        );
        $this->eventEntity2->method('toArray')->willReturn(
            [
                'title' => 'Event 2',
                'start' => '2015-01-22T11:50:00Z',
                'allDay' => true,
            ]
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

        $this->assertEquals($data, $this->serializer->serialize([$this->eventEntity1, $this->eventEntity2]));
    }

    public function testSerializesShouldReturnEmtpyIfEventsAreEmpty()
    {
        $this->assertEquals('[]', $this->serializer->serialize([]));
    }
}
