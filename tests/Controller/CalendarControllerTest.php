<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Controller;

use CalendarBundle\CalendarEvents;
use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use CalendarBundle\Serializer\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CalendarControllerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy|CalendarEvent $calendarEvent;
    private Event|ObjectProphecy $event;
    private ObjectProphecy|EventDispatcherInterface $eventDispatcher;
    private Request|ObjectProphecy $request;
    private SerializerInterface|ObjectProphecy $serializer;
    private CalendarController $controller;

    public function setUp(): void
    {
        $this->calendarEvent = $this->prophesize(CalendarEvent::class);
        $this->event = $this->prophesize(Event::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->request = $this->prophesize(Request::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);

        $this->controller = new CalendarController(
            $this->eventDispatcher->reveal(),
            $this->serializer->reveal()
        );
    }

    public function testItProvidesAnEventsFeedForACalendar(): void
    {
        $this->request->get('start')->willReturn('2016-03-01');
        $this->request->get('end')->willReturn('2016-03-19 15:11:00');
        $this->request->get('filters', '{}')->willReturn('{}');

        $this->calendarEvent->getEvents()->willReturn([$this->event]);

        $this->eventDispatcher(Argument::type(CalendarEvent::class), CalendarEvents::SET_DATA);

        $data = json_encode([
            [
                'title' => 'Birthday!',
                'start' => '2016-03-01',
                'allDay' => true,
            ],
            [
                'title' => 'Flight to somewhere sunny',
                'start' => '2016-03-12T08:55:00Z',
                'allDay' => false,
                'end' => '2016-03-12T11:50:00Z',
            ],
        ]);

        $this->serializer->serialize([$this->event])
            ->willReturn($data)
        ;

        $response = $this->controller->loadAction($this->request->reveal());

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($data, $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testItNotFindAnyEvents(): void
    {
        $this->request->get('start')->willReturn('2016-03-01');
        $this->request->get('end')->willReturn('2016-03-19 15:11:00');
        $this->request->get('filters', '{}')->willReturn('{}');

        $this->calendarEvent->getEvents()->willReturn([$this->event]);
        $this->eventDispatcher(Argument::type(CalendarEvent::class), CalendarEvents::SET_DATA);

        $data = '';

        $this->serializer->serialize([$this->event])
            ->willReturn($data)
        ;

        $response = $this->controller->loadAction($this->request->reveal());

        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals($data, $response->getContent());
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    /**
     * @dataProvider invalidQueryProvider
     */
    #[DataProvider('invalidQueryProvider')]
    public function testItAnswersBadRequestForAnInvalidQuery(array $query, string $message): void
    {
        $this->eventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($message);

        $this->controller->loadAction(Request::create('/fc-load-events', 'GET', $query));
    }

    public static function invalidQueryProvider(): iterable
    {
        $valid = ['start' => '2016-03-01', 'end' => '2016-03-19'];

        yield 'missing start' => [['end' => '2016-03-19'], 'Query parameter "start" should be a string'];
        yield 'start as an array' => [['start' => ['2016-03-01']] + $valid, 'Query parameter "start" should be a string'];
        yield 'missing end' => [['start' => '2016-03-01'], 'Query parameter "end" should be a string'];
        yield 'end as an array' => [['end' => ['2016-03-19']] + $valid, 'Query parameter "end" should be a string'];
        yield 'start not a date' => [['start' => 'foo'] + $valid, 'Query parameter "start" is not a valid date'];
        yield 'end not a date' => [['end' => 'foo'] + $valid, 'Query parameter "end" is not a valid date'];
        yield 'filters not JSON' => [$valid + ['filters' => 'not json'], 'Query parameter "filters" is not valid'];
        yield 'filters a JSON scalar' => [$valid + ['filters' => '1'], 'Query parameter "filters" is not valid'];
    }

    public function testItPassesTheQueryToTheCalendarEvent(): void
    {
        $this->eventDispatcher
            ->dispatch(Argument::that(static fn (CalendarEvent $event): bool => '2016-03-01' === $event->getStart()->format('Y-m-d')
                && '2016-03-19' === $event->getEnd()->format('Y-m-d')
                && ['category' => 'work'] === $event->getFilters()), CalendarEvents::SET_DATA)
            ->willReturn($this->calendarEvent)
            ->shouldBeCalledOnce()
        ;
        $this->calendarEvent->getEvents()->willReturn([]);
        $this->serializer->serialize([])->willReturn('[]');

        $response = $this->controller->loadAction(Request::create('/fc-load-events', 'GET', [
            'start' => '2016-03-01',
            'end' => '2016-03-19',
            'filters' => '{"category":"work"}',
        ]));

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('[]', $response->getContent());
    }

    public function testItPassesTheQueryToTheListeners(): void
    {
        $this->request->get('start')->willReturn('2016-03-01');
        $this->request->get('end')->willReturn('2016-03-19 15:11:00');
        $this->request->get('filters', '{}')->willReturn(['calendar' => 'work']);

        $this->eventDispatcher(
            Argument::that(static fn (CalendarEvent $event): bool => '2016-03-01 00:00:00' === $event->getStart()->format('Y-m-d H:i:s')
                && '2016-03-19 15:11:00' === $event->getEnd()->format('Y-m-d H:i:s')
                && ['calendar' => 'work'] === $event->getFilters()),
            CalendarEvents::SET_DATA
        );
        $this->calendarEvent->getEvents()->willReturn([]);
        $this->serializer->serialize([])->willReturn('[]');

        $response = $this->controller->loadAction($this->request->reveal());

        $this->assertSame('[]', $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testItAnswersNoContentWhenTheSerializerReturnsZero(): void
    {
        $this->request->get('start')->willReturn('2016-03-01');
        $this->request->get('end')->willReturn('2016-03-19');
        $this->request->get('filters', '{}')->willReturn('{}');

        $this->eventDispatcher(Argument::type(CalendarEvent::class), CalendarEvents::SET_DATA);
        $this->calendarEvent->getEvents()->willReturn([]);
        $this->serializer->serialize([])->willReturn('0');

        $this->assertSame(Response::HTTP_NO_CONTENT, $this->controller->loadAction($this->request->reveal())->getStatusCode());
    }

    private function eventDispatcher($event, ?string $eventName = null): void
    {
        $this->eventDispatcher
            ->dispatch($event, $eventName)
            ->willReturn($this->calendarEvent)
        ;
    }
}
