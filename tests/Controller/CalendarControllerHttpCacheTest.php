<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Controller;

use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Request\RequestParser;
use CalendarBundle\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CalendarControllerHttpCacheTest extends TestCase
{
    public function testMatchingGetEtagReturnsEmpty304(): void
    {
        $controller = $this->controller();
        $etag = $controller->load($this->request('GET'))->headers->get('ETag');
        self::assertNotNull($etag);

        $response = $controller->load($this->request('GET', $etag));

        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
        self::assertSame('', $response->getContent());
    }

    public function testDifferentGetEtagReturns200(): void
    {
        $response = $this->controller()->load($this->request('GET', '"different"'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testMatchingPostEtagReturns200(): void
    {
        $controller = $this->controller();
        $etag = $controller->load($this->request('POST'))->headers->get('ETag');
        self::assertNotNull($etag);

        $response = $controller->load($this->request('POST', $etag));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDefaultCacheIsPrivate(): void
    {
        $control = (string) $this->controller()->load($this->request('GET'))->headers->get('Cache-Control');

        self::assertStringContainsString('private', $control);
        self::assertStringNotContainsString('public', $control);
    }

    public function testCachePublicOptionAllowsSharedCaching(): void
    {
        $control = (string) $this->controller(cachePublic: true)->load($this->request('GET'))->headers->get('Cache-Control');

        self::assertStringContainsString('public', $control);
        self::assertStringNotContainsString('private', $control);
    }

    public function testDisabledCacheHasNoEtagAndDoesNotReturn304(): void
    {
        $response = $this->controller(cacheMaxAge: 0)->load($this->request('GET', '"matching"'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse($response->headers->has('ETag'));
    }

    private function controller(int $cacheMaxAge = 300, bool $cachePublic = false): CalendarController
    {
        $dispatcher = self::createStub(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(static fn(object $event): object => $event);

        $serializer = self::createStub(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('[{"title":"Event"}]');

        if ($cachePublic) {
            return new CalendarController($dispatcher, $serializer, new RequestParser(), $cacheMaxAge, $cachePublic);
        }

        return new CalendarController($dispatcher, $serializer, new RequestParser(), $cacheMaxAge);
    }

    private function request(string $method, ?string $etag = null): Request
    {
        $parameters = ['start' => '2024-01-01', 'end' => '2024-01-31'];
        $uri = '/fc-load-events';
        if ('GET' === $method) {
            $uri .= '?' . http_build_query($parameters);
            $parameters = [];
        }

        return Request::create($uri, method: $method, parameters: $parameters, server: null === $etag ? [] : ['HTTP_IF_NONE_MATCH' => $etag]);
    }
}
