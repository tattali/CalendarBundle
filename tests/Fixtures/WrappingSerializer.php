<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Fixtures;

use CalendarBundle\Serializer\SerializerInterface;

/**
 * Decorates the bundle's serializer, as an application would.
 */
final class WrappingSerializer implements SerializerInterface
{
    public function __construct(
        private readonly SerializerInterface $inner,
    ) {
    }

    public function serialize(array $events): string
    {
        return '{"events":'.$this->inner->serialize($events).'}';
    }
}
