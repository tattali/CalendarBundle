<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\DependencyInjection;

use CalendarBundle\DependencyInjection\CalendarExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CalendarExtensionTest extends TestCase
{
    private ContainerBuilder $builder;
    private CalendarExtension $loader;
    private array $configuration;

    public function setUp(): void
    {
        $this->builder = new ContainerBuilder();
        $this->loader = new CalendarExtension();

        $this->configuration = [];
    }

    public function testConfiguration(): void
    {
        $this->loader->load($this->configuration, $this->builder);

        $this->assertEquals($this->configuration, $this->builder->getParameterBag()->all());
    }
}
