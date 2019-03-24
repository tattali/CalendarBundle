<?php

namespace CalendarBundle\Tests\DependencyInjection;

use CalendarBundle\DependencyInjection\Configuration;
use CalendarBundle\DependencyInjection\CalendarExtension;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CalendarExtensionTest extends TestCase
{
    public function setUp(): void
    {
        $this->builder = new ContainerBuilder();
        $this->loader = new CalendarExtension();

        $this->configuration = [];
    }

    public function testConfiguration()
    {
        $this->loader->load($this->configuration, $this->builder);

        $this->assertEquals($this->configuration, $this->builder->getParameterBag()->all());
    }
}
