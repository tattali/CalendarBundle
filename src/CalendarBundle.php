<?php

declare(strict_types=1);

namespace CalendarBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class CalendarBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();
        \assert($rootNode instanceof \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition);

        $rootNode
            ->children()
                ->integerNode('cache_max_age')
                    ->defaultValue(300)
                    ->min(0)
                    ->info('HTTP cache max-age in seconds (0 to disable)')
                ->end()
                ->integerNode('json_max_depth')
                    ->defaultValue(4)
                    ->min(1)
                    ->max(512)
                    ->info('Maximum JSON nesting depth for filters parameter')
                ->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $container->import('../config/services.yaml');

        $container->services()
            ->get('CalendarBundle\Controller\CalendarController')
            ->arg('$cacheMaxAge', $config['cache_max_age'])
        ;

        $container->services()
            ->get('CalendarBundle\Request\RequestParser')
            ->arg('$jsonMaxDepth', $config['json_max_depth'])
        ;
    }
}
