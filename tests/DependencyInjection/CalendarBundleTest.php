<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\DependencyInjection;

use CalendarBundle\CalendarBundle;
use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Serializer\Serializer;
use CalendarBundle\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class CalendarBundleTest extends TestCase
{
    public function testLoadExtensionRegistersServices(): void
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        self::assertTrue($container->hasDefinition(Serializer::class));
        self::assertTrue($container->hasDefinition(CalendarController::class));
        self::assertTrue($container->hasAlias(SerializerInterface::class));
    }

    public function testSerializerServiceConfiguration(): void
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        $definition = $container->getDefinition(Serializer::class);
        self::assertSame(Serializer::class, $definition->getClass());
    }

    public function testControllerServiceConfiguration(): void
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        $definition = $container->getDefinition(CalendarController::class);
        self::assertSame(CalendarController::class, $definition->getClass());
        self::assertTrue($definition->isPublic());
        self::assertTrue($definition->isAutowired());
    }

    public function testBundleExtendsAbstractBundle(): void
    {
        $bundle = new CalendarBundle();

        self::assertInstanceOf(AbstractBundle::class, $bundle);
    }

    public function testBundleLoadExtension(): void
    {
        $bundle = new CalendarBundle();
        $container = new ContainerBuilder();
        $bundlePath = \dirname(__DIR__, 2) . '/src';

        $locator = new FileLocator($bundlePath);
        $resolver = new \Symfony\Component\Config\Loader\LoaderResolver([
            new YamlFileLoader($container, $locator),
            new \Symfony\Component\DependencyInjection\Loader\PhpFileLoader($container, $locator),
        ]);
        $loader = new \Symfony\Component\DependencyInjection\Loader\PhpFileLoader($container, $locator);
        $loader->setResolver($resolver);
        $instanceOf = [];

        $configurator = new ContainerConfigurator($container, $loader, $instanceOf, $bundlePath, 'calendar_test');

        $bundle->loadExtension([], $configurator, $container);

        self::assertTrue($container->hasDefinition(Serializer::class));
        self::assertTrue($container->hasDefinition(CalendarController::class));
    }
}
