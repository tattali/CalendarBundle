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
        $start = new \DateTime($request->get('start'));
        $end = new \DateTime($request->get('end'));
        $filters = $request->get('filters', '{}');
        $filters = \is_array($filters) ? $filters : json_decode($filters, true);

        $event = $this->eventDispatcher->dispatch(
            new CalendarEvent($start, $end, $filters),
            CalendarEvents::SET_DATA
        );
        $content = $this->serializer->serialize($event->getEvents());

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($content);
        $response->setStatusCode(empty($content) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK);

        return $response;
    }
}
