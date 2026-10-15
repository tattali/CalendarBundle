<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Controller;

use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Request\RequestParser;
use CalendarBundle\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CalendarControllerFiltersTest extends TestCase
{
    public function testNonObjectFiltersReturnBadRequest(): void
    {
        $controller = new CalendarController(
            self::createStub(EventDispatcherInterface::class),
            self::createStub(SerializerInterface::class),
            new RequestParser(),
        );
        $request = Request::create('/fc-load-events', method: 'POST', parameters: [
            'start' => '2024-01-01',
            'end' => '2024-01-31',
            'filters' => '5',
        ]);

        try {
            $controller->load($request);
            self::fail('Expected a bad request for non-object filters.');
        } catch (BadRequestHttpException $e) {
            self::assertSame(400, $e->getStatusCode());
            self::assertSame('Query parameter "filters" must be a JSON object or array', $e->getMessage());
        }
    }
}
