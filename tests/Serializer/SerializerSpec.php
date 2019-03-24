<?php

namespace Tests\CalendarBundle\Serializer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use CalendarBundle\Entity\Event;
use CalendarBundle\Serializer\Serializer;

class SerializerSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Serializer::class);
    }

    public function it_serialzes_data_successfully(Event $event1, Event $event2)
    {
        $event1->toArray()->shouldBeCalled()->willReturn(
            [
                'title' => 'Event 1',
                'start' => '2015-01-20T11:50:00Z',
                'allDay' => false,
                'end' => '2015-01-21T11:50:00Z',
            ]
        );
        $event2->toArray()->shouldBeCalled()->willReturn(
            [
                'title' => 'Event 2',
                'start' => '2015-01-22T11:50:00Z',
                'allDay' => true,
            ]
        );

        $data = [
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
            ]
        ];

        $this
            ->serialize([$event1, $event2])
            ->shouldReturn(json_encode($data));
    }

    public function it_serialzes_should_return_emtpy_if_events_are_empty()
    {
        $this->serialize([])->shouldReturn('[]');
    }
}
