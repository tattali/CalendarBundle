<?php

namespace Tests\CalendarBundle\Controller;

use CalendarBundle\CalendarEvents;
use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use CalendarBundle\Serializer\SerializerInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CalendarControllerSpec extends ObjectBehavior
{
    public function let(
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer
    ) {
        $this->beConstructedWith($eventDispatcher, $serializer);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CalendarController::class);
    }

    public function it_is_a_Symfony_controller()
    {
        $this->shouldHaveType(AbstractController::class);
    }

    public function it_provides_an_events_feed_for_a_calendar(
        CalendarEvent $calendarEvent,
        Event $event,
        EventDispatcherInterface $eventDispatcher,
        Request $request,
        SerializerInterface $serializer
    ) {
        $request->get('start')->willReturn('2016-03-01');
        $request->get('end')->willReturn('2016-03-19 15:11:00');
        $request->get('filters', [])->willReturn([]);
        $events = [$event];

        $eventDispatcher
            ->dispatch(CalendarEvents::SET_DATA, Argument::type(CalendarEvent::class))
            ->shouldBeCalled()
            ->willReturn($calendarEvent);

        $data = <<<'JSON'
  [
    {
      "title": "Birthday!",
      "start": "2016-03-01",
      "allDay": true,
    }, {
      "title": "Flight to somewhere sunny",
      "start": "2016-03-12T08:55:00Z",
      "allDay": false,
      "end": "2016-03-12T11:50:00Z"
    }
  ]
JSON;

        $calendarEvent->getEvents()->shouldBeCalled()->willReturn($events);
        $serializer->serialize($events)->shouldBeCalled()->willReturn($data);

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($data);
        $response->setStatusCode(Response::HTTP_OK);

        $this->load($request)->shouldBeLike($response);
    }

    public function it_not_find_any_events(
        CalendarEvent $calendarEvent,
        EventDispatcherInterface $eventDispatcher,
        Request $request,
        SerializerInterface $serializer
    ) {
        $request->get('start')->willReturn('2016-03-01');
        $request->get('end')->willReturn('2016-03-19 15:11:00');
        $request->get('filters', [])->willReturn([]);
        $events = [];

        $eventDispatcher
            ->dispatch(CalendarEvents::SET_DATA, Argument::type(CalendarEvent::class))
            ->shouldBeCalled()
            ->willReturn($calendarEvent);

        $data = '';

        $calendarEvent->getEvents()->shouldBeCalled()->willReturn($events);
        $serializer->serialize($events)->shouldBeCalled()->willReturn($data);

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($data);
        $response->setStatusCode(Response::HTTP_NO_CONTENT);

        $this->load($request)->shouldBeLike($response);
    }
}
