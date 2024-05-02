<?php

declare(strict_types=1);

namespace CalendarBundle\Serializer;

use CalendarBundle\Entity\Event;

class Serializer implements SerializerInterface
{
    /**
     * @param Event[] $events
     */
    public function serialize(array $events): string
    {
        $result = [];

        foreach ($events as $event) {
            $result[] = $event->toArray();
        }

        return json_encode($result, \JSON_THROW_ON_ERROR);
    }
}
