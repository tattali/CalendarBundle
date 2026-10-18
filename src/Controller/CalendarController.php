<?php

declare(strict_types=1);

namespace CalendarBundle\Controller;

use CalendarBundle\CalendarEvents;
use CalendarBundle\Event\CalendarEvent;
use CalendarBundle\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CalendarController
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected SerializerInterface $serializer
    )
    {}

    /**
     * @throws \Exception
     */
    public function loadAction(Request $request): Response
    {
        $start = new \DateTime(self::stringParameter($request, 'start'));
        $end = new \DateTime(self::stringParameter($request, 'end'));
        $filters = $request->get('filters', '{}');
        $filters = \is_string($filters) ? json_decode($filters, true) : $filters;
        if (!\is_array($filters)) {
            throw new \TypeError(sprintf('The "filters" parameter must be an array or a JSON object, "%s" given.', get_debug_type($filters)));
        }

        $event = $this->eventDispatcher->dispatch(
            new CalendarEvent($start, $end, $filters),
            CalendarEvents::SET_DATA
        );
        $content = $this->serializer->serialize($event->getEvents());

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($content);
        $response->setStatusCode(\in_array($content, ['', '0'], true) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);

        return $response;
    }

    private static function stringParameter(Request $request, string $name): string
    {
        $value = $request->get($name);
        if (!\is_string($value)) {
            throw new \TypeError(sprintf('The "%s" parameter must be a string, "%s" given.', $name, get_debug_type($value)));
        }

        return $value;
    }
}
