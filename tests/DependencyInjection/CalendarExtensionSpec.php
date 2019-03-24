<?php

namespace Tests\CalendarBundle\DependencyInjection;

use CalendarBundle\DependencyInjection\CalendarExtension;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class CalendarExtensionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(CalendarExtension::class);
    }

    public function it_should_set_parameters_correctly(ContainerBuilder $container)
    {
        $container->fileExists(Argument::type('string'))->willReturn(true);
        $container->setParameter(Argument::type('string'), Argument::any())->will(function() {
            return;
        });
        $container->setDefinition(Argument::any(), Argument::any())->will(function() {
            return;
        });

        $configuration = [];
        $this->load($configuration, $container);
    }
}
