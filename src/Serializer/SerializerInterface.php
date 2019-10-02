<?php

declare(strict_types=1);

namespace CalendarBundle\Serializer;

use CalendarBundle\Entity\Event;

interface SerializerInterface
{
    /**
     * @param Event[] $events
     */
    public function serialize(array $events): string;
}
