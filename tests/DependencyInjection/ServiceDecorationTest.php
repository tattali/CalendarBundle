<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\DependencyInjection;

use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Request\RequestParser;
use CalendarBundle\Serializer\SerializerInterface;
use CalendarBundle\Tests\Fixtures\CountingRequestParser;
use CalendarBundle\Tests\Fixtures\WrappingSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

final class ServiceDecorationTest extends TestCase
{
    public function testDecoratingTheBundleServicesChangesWhatTheControllerUses(): void
    {
        $container = new ContainerBuilder();
        $container->register('event_dispatcher', EventDispatcher::class);
        new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'))->load('services.yaml');
        $container->register(CountingRequestParser::class, CountingRequestParser::class)
            ->setDecoratedService(RequestParser::class)
            ->setArgument('$inner', new Reference('.inner'))
            ->setPublic(true)
        ;
        $container->register(WrappingSerializer::class, WrappingSerializer::class)
            ->setDecoratedService(SerializerInterface::class)
            ->setArgument('$inner', new Reference('.inner'))
        ;
        $container->compile();

        $controller = $container->get(CalendarController::class);
        self::assertInstanceOf(CalendarController::class, $controller);
        $response = $controller->load(Request::create('/fc-load-events', 'GET', [
            'start' => '2024-01-01',
            'end' => '2024-01-08',
        ]));

        self::assertSame('{"events":[]}', $response->getContent());
        $parser = $container->get(CountingRequestParser::class);
        self::assertInstanceOf(CountingRequestParser::class, $parser);
        self::assertSame(1, $parser->calls);
    }
}
