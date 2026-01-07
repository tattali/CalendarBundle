<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\DependencyInjection;

use CalendarBundle\CalendarBundle;
use CalendarBundle\Controller\CalendarController;
use CalendarBundle\Serializer\Serializer;
use CalendarBundle\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Loader\DefinitionFileLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
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

    public function testBundleConfigureDefinesOptions(): void
    {
        $bundle = new CalendarBundle();
        $treeBuilder = new TreeBuilder('calendar');
        $loader = $this->createMock(DefinitionFileLoader::class);

        $configurator = new DefinitionConfigurator($treeBuilder, $loader, '', __FILE__);
        $bundle->configure($configurator);

        $tree = $treeBuilder->buildTree();
        self::assertTrue($tree instanceof ArrayNode);
        $children = $tree->getChildren();

        self::assertArrayHasKey('cache_max_age', $children);
        self::assertArrayHasKey('json_max_depth', $children);
    }

    public function testBundleConfigureDefaultValues(): void
    {
        $bundle = new CalendarBundle();
        $treeBuilder = new TreeBuilder('calendar');
        $loader = $this->createMock(DefinitionFileLoader::class);

        $configurator = new DefinitionConfigurator($treeBuilder, $loader, '', __FILE__);
        $bundle->configure($configurator);

        $tree = $treeBuilder->buildTree();
        $config = (new Processor())->process($tree, []);

        self::assertSame(300, $config['cache_max_age']);
        self::assertSame(4, $config['json_max_depth']);
    }

    public function testBundleConfigureValidatesMinValues(): void
    {
        $bundle = new CalendarBundle();
        $treeBuilder = new TreeBuilder('calendar');
        $loader = $this->createMock(DefinitionFileLoader::class);

        $configurator = new DefinitionConfigurator($treeBuilder, $loader, '', __FILE__);
        $bundle->configure($configurator);

        $tree = $treeBuilder->buildTree();
        $config = (new Processor())->process($tree, [
            ['cache_max_age' => 0, 'json_max_depth' => 1],
        ]);

        self::assertSame(0, $config['cache_max_age']);
        self::assertSame(1, $config['json_max_depth']);
    }

    public function testBundleLoadExtension(): void
    {
        $bundle = new CalendarBundle();
        $container = new ContainerBuilder();
        $bundlePath = \dirname(__DIR__, 2) . '/src';

        $locator = new FileLocator($bundlePath);
        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
        ]);
        $loader = new PhpFileLoader($container, $locator);
        $loader->setResolver($resolver);
        $instanceOf = [];

        $configurator = new ContainerConfigurator($container, $loader, $instanceOf, $bundlePath, 'calendar_test');

        $bundle->loadExtension([
            'cache_max_age' => 300,
            'json_max_depth' => 4,
        ], $configurator, $container);

        self::assertTrue($container->hasDefinition(Serializer::class));
        self::assertTrue($container->hasDefinition(CalendarController::class));
    }
}
