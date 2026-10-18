<?php

declare(strict_types=1);

namespace CalendarBundle\Controller;

use CalendarBundle\CalendarEvents;
use CalendarBundle\Event\CalendarEvent;
use CalendarBundle\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        $start = $request->get('start');
        if (!\is_string($start)) {
            throw new BadRequestHttpException('Query parameter "start" should be a string');
        }
        $start = new \DateTime($start);

        $end = $request->get('end');
        if (!\is_string($end)) {
            throw new BadRequestHttpException('Query parameter "end" should be a string');
        }
        $end = new \DateTime($end);

        $filters = $request->get('filters', '{}');
        $filters = \is_string($filters) ? json_decode($filters, true) : $filters;
        if (!\is_array($filters)) {
            throw new BadRequestHttpException('Query parameter "filters" is not valid');
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
}
