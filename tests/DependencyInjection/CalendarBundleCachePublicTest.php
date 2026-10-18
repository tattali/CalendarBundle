<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\DependencyInjection;

use CalendarBundle\CalendarBundle;
use CalendarBundle\Controller\CalendarController;
use PHPUnit\Framework\TestCase;
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

final class CalendarBundleCachePublicTest extends TestCase
{
    public function testCachePublicDefaultsToFalse(): void
    {
        $treeBuilder = new TreeBuilder('calendar');
        $loader = self::createStub(DefinitionFileLoader::class);
        new CalendarBundle()->configure(new DefinitionConfigurator($treeBuilder, $loader, '', __FILE__));

        $config = new Processor()->process($treeBuilder->buildTree(), []);

        self::assertArrayHasKey('cache_public', $config);
        self::assertFalse($config['cache_public']);
    }

    public function testLoadExtensionPassesCachePublicToController(): void
    {
        $bundle = new CalendarBundle();
        $container = new ContainerBuilder();
        $bundlePath = \dirname(__DIR__, 2).'/src';
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
            'cache_public' => true,
            'json_max_depth' => 4,
        ], $configurator, $container);

        self::assertTrue($container->getDefinition(CalendarController::class)->getArgument('$cachePublic'));
    }
}
