<?php

declare(strict_types=1);

namespace CalendarBundle\Controller;

use CalendarBundle\CalendarEvents;
use CalendarBundle\Event\CalendarEvent;
use CalendarBundle\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CalendarController extends AbstractController
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer
    ) {
        if (class_exists(LegacyEventDispatcherProxy::class)) {
            $eventDispatcher = LegacyEventDispatcherProxy::decorate($eventDispatcher);
        }
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function loadAction(Request $request): Response
    {
        $start = new \DateTime($request->get('start'));
        $end = new \DateTime($request->get('end'));
        $filters = $request->get('filters', '{}');
        $filters = \is_array($filters) ? $filters : json_decode($filters, true);

        $event = $this->dispatchWithBC(
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

    private function dispatchWithBC($event, string $eventName)
    {
        if (class_exists(LegacyEventDispatcherProxy::class)) {
            return $this->eventDispatcher->dispatch($event, $eventName);
        }

        return $this->eventDispatcher->dispatch($eventName, $event);
    }
}
