<?php

declare(strict_types=1);

namespace CalendarBundle\Serializer;

use CalendarBundle\Entity\Event;

class Serializer implements SerializerInterface
{
    /**
     * @param Event[] $events
     *
     * @return string json
     */
    public function serialize(array $events): string
    {
        $result = [];

        foreach ($events as $event) {
            $result[] = $event->toArray();
        }

        $json = json_encode($result);
        if (false === $json) {
            throw new \TypeError(sprintf('The events cannot be encoded to JSON: %s.', json_last_error_msg()));
        }

        return $json;
    }
}
