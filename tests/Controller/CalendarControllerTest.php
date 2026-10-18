<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Controller;

use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use CalendarBundle\Serializer\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CalendarControllerTest extends TestCase
{
    private MockObject&SetDataEvent $calendarEvent;
    private MockObject&Event $event;

    private MockObject&EventDispatcherInterface $eventDispatcher;
    private MockObject&SerializerInterface $serializer;

    private CalendarController $controller;

    protected function setUp(): void
    {
        $this->calendarEvent = $this->createMock(SetDataEvent::class);
        $this->event = $this->createMock(Event::class);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->controller = new CalendarController(
            $this->eventDispatcher,
            $this->serializer,
        );
    }

    public function testItProvidesAnEventsFeedForACalendar(): void
    {
        $this->calendarEvent->method('getEvents')
            ->willReturn([$this->event])
        ;

        $this->eventDispatcher->method('dispatch')
            ->with(self::isInstanceOf(SetDataEvent::class))
            ->willReturn($this->calendarEvent)
        ;

        $data = json_encode([
            [
                'title' => 'Birthday!',
                'start' => '2016-03-01T12:55:00Z',
                'allDay' => true,
            ],
            [
                'title' => 'Flight to somewhere sunny',
                'start' => '2016-03-12T08:55:00Z',
                'allDay' => false,
                'end' => '2016-03-12T11:50:00Z',
            ],
        ]);

        $this->serializer->method('serialize')
            ->with([$this->event])
            ->willReturn($data)
        ;

        $response = $this->controller->load(Request::create('/', 'GET', [
            'start' => '2016-03-01',
            'end' => '2016-03-19',
            'filters' => '{}',
        ]));

        self::assertInstanceOf(JsonResponse::class, $response);

        self::assertSame($data, $response->getContent());
        self::assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());
    }

    public function testItNotFindAnyEvents(): void
    {
        $this->calendarEvent->method('getEvents')
            ->willReturn([$this->event])
        ;

        $this->eventDispatcher->method('dispatch')
            ->with(self::isInstanceOf(SetDataEvent::class))
            ->willReturn($this->calendarEvent)
        ;

        $data = '';

        $this->serializer->method('serialize')
            ->with([$this->event])
            ->willReturn($data)
        ;

        $response = $this->controller->load(Request::create('/', 'GET', [
            'start' => '2016-03-01',
            'end' => '2016-03-19',
            'filters' => '{}',
        ]));

        self::assertInstanceOf(JsonResponse::class, $response);

        self::assertSame($data, $response->getContent());
        self::assertSame(JsonResponse::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testItShouldThrowErrorOnStartParam(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->controller->load(Request::create('/', 'GET', [
            'start' => '',
            'end' => '',
        ]));
    }

    public function testItShouldThrowErrorOnEndParam(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->controller->load(Request::create('/', 'GET', [
            'start' => '2016-03-01',
            'end' => '',
        ]));
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('queryWithAnInvalidDateProvider')]
    public function testItAnswersBadRequestWhenADateCannotBeParsed(array $query, string $message): void
    {
        $this->eventDispatcher->expects(self::never())->method('dispatch');

        try {
            $this->controller->load(Request::create('/', 'GET', $query));
            self::fail('A BadRequestHttpException should have been thrown.');
        } catch (BadRequestHttpException $e) {
            self::assertSame(400, $e->getStatusCode());
            self::assertSame($message, $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function queryWithAnInvalidDateProvider(): iterable
    {
        yield 'start not a date' => [['start' => 'foo', 'end' => '2016-03-19'], 'Query parameter "start" is not a valid date'];
        yield 'end not a date' => [['start' => '2016-03-01', 'end' => 'foo'], 'Query parameter "end" is not a valid date'];
    }

    public function testItReadsTheQueryStringOfAGetRequest(): void
    {
        $setDataEvent = $this->loadAndCaptureSetDataEvent(Request::create('/', 'GET', [
            'start' => '2016-03-01',
            'end' => '2016-03-19',
            'filters' => '{"calendar-id":"holidays"}',
        ]));

        self::assertSame('2016-03-01', $setDataEvent->getStart()->format('Y-m-d'));
        self::assertSame('2016-03-19', $setDataEvent->getEnd()->format('Y-m-d'));
        self::assertSame(['calendar-id' => 'holidays'], $setDataEvent->getFilters());
    }

    public function testItReadsFiltersGivenAsAnArrayInTheQueryString(): void
    {
        $setDataEvent = $this->loadAndCaptureSetDataEvent(Request::create('/', 'GET', [
            'start' => '2016-03-01',
            'end' => '2016-03-19',
            'filters' => ['calendar-id' => 'holidays'],
        ]));

        self::assertSame(['calendar-id' => 'holidays'], $setDataEvent->getFilters());
    }

    public function testItUsesEmptyFiltersWhenNoneAreGiven(): void
    {
        $setDataEvent = $this->loadAndCaptureSetDataEvent(Request::create('/', 'GET', [
            'start' => '2016-03-01',
            'end' => '2016-03-19',
        ]));

        self::assertSame([], $setDataEvent->getFilters());
    }

    public function testItReadsTheBodyOfAPostRequest(): void
    {
        $setDataEvent = $this->loadAndCaptureSetDataEvent(Request::create('/', 'POST', [
            'start' => '2016-03-01',
            'end' => '2016-03-19',
            'filters' => '{"calendar-id":"holidays"}',
        ]));

        self::assertSame('2016-03-01', $setDataEvent->getStart()->format('Y-m-d'));
        self::assertSame('2016-03-19', $setDataEvent->getEnd()->format('Y-m-d'));
        self::assertSame(['calendar-id' => 'holidays'], $setDataEvent->getFilters());
    }

    public function testTheQueryStringTakesPrecedenceOverThePostBody(): void
    {
        $setDataEvent = $this->loadAndCaptureSetDataEvent(Request::create('/?start=2016-03-01', 'POST', [
            'start' => '2017-01-01',
            'end' => '2017-01-31',
        ]));

        self::assertSame('2016-03-01', $setDataEvent->getStart()->format('Y-m-d'));
        self::assertSame('2017-01-31', $setDataEvent->getEnd()->format('Y-m-d'));
    }

    public function testRequestAttributesTakePrecedenceOverTheQueryString(): void
    {
        $request = Request::create('/', 'GET', [
            'start' => '2016-03-01',
            'end' => '2016-03-19',
        ]);
        $request->attributes->set('start', '2020-05-01');

        $setDataEvent = $this->loadAndCaptureSetDataEvent($request);

        self::assertSame('2020-05-01', $setDataEvent->getStart()->format('Y-m-d'));
        self::assertSame('2016-03-19', $setDataEvent->getEnd()->format('Y-m-d'));
    }

    public function testARequestAttributeSetToNullHidesTheQueryString(): void
    {
        $request = Request::create('/', 'GET', [
            'start' => '2016-03-01',
            'end' => '2016-03-19',
            'filters' => '{}',
        ]);
        $request->attributes->set('filters', null);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Query parameter "filters" is not valid');

        $this->controller->load($request);
    }

    private function loadAndCaptureSetDataEvent(Request $request): SetDataEvent
    {
        $dispatched = null;

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatched): object {
                return $dispatched = $event;
            })
        ;

        $this->serializer->method('serialize')
            ->willReturn('[]')
        ;

        $this->controller->load($request);

        self::assertInstanceOf(SetDataEvent::class, $dispatched);

        return $dispatched;
    }
}
