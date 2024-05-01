<?php

declare(strict_types=1);

namespace CalendarBundle\Controller;

use CalendarBundle\Event\SetDataEvent;
use CalendarBundle\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CalendarController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SerializerInterface $serializer,
    ) {}

    public function load(Request $request): JsonResponse
    {
        $start = new \DateTime($request->get('start'));
        $end = new \DateTime($request->get('end'));
        $filters = $request->get('filters', '{}');
        $filters = \is_array($filters) ? $filters : json_decode($filters, true);

        $setDataEvent = $this->eventDispatcher->dispatch(new SetDataEvent($start, $end, $filters));

        $content = $this->serializer->serialize($setDataEvent->getEvents());

        return new JsonResponse(
            $content,
            empty($content) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK,
        );
    }
}
