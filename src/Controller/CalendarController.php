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
        $start = self::dateParameter($request, 'start');
        $end = self::dateParameter($request, 'end');

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

    private static function dateParameter(Request $request, string $name): \DateTime
    {
        $value = $request->get($name);
        if (!\is_string($value)) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" should be a string', $name));
        }

        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" is not a valid date', $name), $e);
        }
    }
}
