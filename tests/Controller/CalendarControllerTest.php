<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Controller;

use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use CalendarBundle\Serializer\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CalendarControllerTest extends TestCase
{
    private Event&MockObject $event;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private SerializerInterface&MockObject $serializer;
    private SetDataEvent&MockObject $calendarEvent;
    private Request&MockObject $request;

    private CalendarController $controller;

    protected function setUp(): void
    {
        $this->calendarEvent = $this->createMock(SetDataEvent::class);
        $this->event = $this->createMock(Event::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->controller = new CalendarController(
            $this->eventDispatcher,
            $this->serializer,
        );
    }

    public function testItProvidesAnEventsFeedForACalendar(): void
    {
        $this->request->method('get')
            ->willReturnCallback(static fn (string $key) => match ($key) {
                'start' => '2016-03-01',
                'end' => '2016-03-19',
                'filters' => '{}',
                default => throw new \LogicException(),
            })
        ;

        $this->calendarEvent->method('getEvents')
            ->willReturn([$this->event])
        ;

        $this->eventDispatcher->method('dispatch')
            ->with(self::isInstanceOf(SetDataEvent::class))
            ->willReturnReference($this->calendarEvent)
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

        $response = $this->controller->load($this->request);

        self::assertInstanceOf(JsonResponse::class, $response);

        self::assertSame($data, $response->getContent());
        self::assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());
    }

    public function testItNotFindAnyEvents(): void
    {
        $this->request->method('get')
            ->willReturnCallback(static fn (string $key) => match ($key) {
                'start' => '2016-03-01',
                'end' => '2016-03-19',
                'filters' => '{}',
                default => throw new \LogicException(),
            })
        ;

        $this->calendarEvent->method('getEvents')
            ->willReturn([$this->event])
        ;

        $this->eventDispatcher->method('dispatch')
            ->with(self::isInstanceOf(SetDataEvent::class))
            ->willReturnReference($this->calendarEvent)
        ;

        $data = '';

        $this->serializer->method('serialize')
            ->with([$this->event])
            ->willReturn($data)
        ;

        $response = $this->controller->load($this->request);

        self::assertInstanceOf(JsonResponse::class, $response);

        self::assertSame($data, $response->getContent());
        self::assertSame(JsonResponse::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testItShouldThrowErrorOnStartParam(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->request->method('get')
            ->willReturnCallback(static fn (string $key) => match ($key) {
                'start' => '',
                'end' => '',
                default => throw new \LogicException(),
            })
        ;

        $this->controller->load($this->request);
    }

    public function testItShouldThrowErrorOnEndParam(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->request->method('get')
            ->willReturnCallback(static fn (string $key) => match ($key) {
                'start' => '2016-03-01',
                'end' => '',
                default => throw new \LogicException(),
            })
        ;

        $this->controller->load($this->request);
    }
}
