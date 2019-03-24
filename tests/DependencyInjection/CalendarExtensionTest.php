<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\DependencyInjection;

use CalendarBundle\DependencyInjection\CalendarExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CalendarExtensionTest extends TestCase
{
    private $builder;
    private $loader;
    private $configuration;

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
