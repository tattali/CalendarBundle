<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Controller;

use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Event\SetDataEvent;
use CalendarBundle\Request\RequestParser;
use CalendarBundle\Serializer\Serializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

final class CalendarControllerImmutableDatesTest extends TestCase
{
    public function testListenersReceiveImmutableDates(): void
    {
        $received = [];
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(SetDataEvent::class, static function (SetDataEvent $event) use (&$received): void {
            $received = [$event->getStart()::class, $event->getEnd()::class];
        });

        $controller = new CalendarController($eventDispatcher, new Serializer(), new RequestParser());
        $controller->load(Request::create('/fc-load-events', 'GET', [
            'start' => '2026-10-01T00:00:00',
            'end' => '2026-11-01T00:00:00',
        ]));

        self::assertSame([\DateTimeImmutable::class, \DateTimeImmutable::class], $received);
    }
}
