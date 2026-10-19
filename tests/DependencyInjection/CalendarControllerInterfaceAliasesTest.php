<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\DependencyInjection;

use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Request\QueryParameters;
use CalendarBundle\Request\RequestParserInterface;
use CalendarBundle\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CalendarControllerInterfaceAliasesTest extends TestCase
{
    public function testTheInterfacesPointToTheBundleServicesByDefault(): void
    {
        $container = self::createContainer();
        $container->compile();

        $response = self::controller($container)->load(Request::create('/fc-load-events', 'GET', [
            'start' => '2024-01-01',
            'end' => '2024-01-08',
        ]));
        self::assertSame('[]', $response->getContent());

        $this->expectException(BadRequestHttpException::class);
        self::controller($container)->load(Request::create('/fc-load-events', 'GET'));
    }

    public function testTheControllerUsesTheServiceBehindTheSerializerInterface(): void
    {
        $container = self::createContainer();
        $container->register('app.serializer', SerializerInterface::class)->setSynthetic(true)->setPublic(true);
        $container->setAlias(SerializerInterface::class, 'app.serializer');
        $container->compile();

        $container->set('app.serializer', new class implements SerializerInterface {
            public function serialize(array $events): string
            {
                return '[{"title":"From the app serializer"}]';
            }
        });

        $response = self::controller($container)->load(Request::create('/fc-load-events', 'GET', [
            'start' => '2024-01-01',
            'end' => '2024-01-08',
        ]));

        self::assertSame('[{"title":"From the app serializer"}]', $response->getContent());
    }

    public function testTheControllerUsesTheServiceBehindTheRequestParserInterface(): void
    {
        $container = self::createContainer();
        $container->register('app.request_parser', RequestParserInterface::class)->setSynthetic(true)->setPublic(true);
        $container->setAlias(RequestParserInterface::class, 'app.request_parser');
        $container->compile();

        $parser = new class implements RequestParserInterface {
            public int $calls = 0;

            public function parse(Request $request): QueryParameters
            {
                ++$this->calls;

                return new QueryParameters(new \DateTimeImmutable('2024-01-01'), new \DateTimeImmutable('2024-01-08'), []);
            }
        };
        $container->set('app.request_parser', $parser);

        // no start nor end: the bundle's parser would answer 400
        $response = self::controller($container)->load(Request::create('/fc-load-events', 'GET'));

        self::assertSame(1, $parser->calls);
        self::assertSame('[]', $response->getContent());
    }

    private static function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register('event_dispatcher', EventDispatcher::class);
        new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'))->load('services.yaml');

        return $container;
    }

    private static function controller(ContainerBuilder $container): CalendarController
    {
        $controller = $container->get(CalendarController::class);
        self::assertInstanceOf(CalendarController::class, $controller);

        return $controller;
    }
}
