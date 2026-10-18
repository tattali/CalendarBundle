<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Controller;

use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Event\SetDataEvent;
use CalendarBundle\Exception\InvalidTimeZoneException;
use CalendarBundle\Request\RequestParser;
use CalendarBundle\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CalendarControllerTimeZoneTest extends TestCase
{
    public function testItPassesTheRequestedTimeZoneToTheSetDataEvent(): void
    {
        $dispatched = null;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event) use (&$dispatched): bool {
                $dispatched = $event;

                return $event instanceof SetDataEvent;
            }))
            ->willReturnArgument(0)
        ;

        $serializer = self::createStub(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('[]');

        $controller = new CalendarController($eventDispatcher, $serializer, new RequestParser());

        $controller->load(Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2026-10-01T00:00:00',
            'end' => '2026-11-01T00:00:00',
            'timeZone' => 'America/New_York',
        ]));

        self::assertInstanceOf(SetDataEvent::class, $dispatched);
        self::assertNotNull($dispatched->getTimeZone());
        self::assertSame('America/New_York', $dispatched->getTimeZone()->getName());
        self::assertSame('2026-10-01T00:00:00-04:00', $dispatched->getStart()->format(\DateTime::ATOM));
        self::assertSame('2026-11-01T00:00:00-04:00', $dispatched->getEnd()->format(\DateTime::ATOM));
    }

    public function testItAnswersBadRequestOnAnInvalidTimeZone(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::never())->method('dispatch');

        $controller = new CalendarController(
            $eventDispatcher,
            self::createStub(SerializerInterface::class),
            new RequestParser(),
        );

        try {
            $controller->load(Request::create('/fc-load-events', method: 'POST', parameters: [
                'start' => '2026-10-01T00:00:00',
                'end' => '2026-11-01T00:00:00',
                'timeZone' => 'Mars/Olympus_Mons',
            ]));
            self::fail('An invalid time zone must answer 400');
        } catch (BadRequestHttpException $e) {
            self::assertSame('Query parameter "timeZone" is not a valid time zone', $e->getMessage());
            self::assertInstanceOf(InvalidTimeZoneException::class, $e->getPrevious());
        }
    }
}
