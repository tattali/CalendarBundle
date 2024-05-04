<?php

declare(strict_types=1);

namespace CalendarBundle\Controller;

use CalendarBundle\Event\SetDataEvent;
use CalendarBundle\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CalendarController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SerializerInterface $serializer,
    ) {}

    public function load(Request $request): JsonResponse
    {
        try {
            $start = $request->get('start');
            if ($start && \is_string($start)) {
                $start = new \DateTime($start);
            } else {
                throw new \Exception('Query parameter "start" should be a string');
            }

            $end = $request->get('end');
            if ($end && \is_string($end)) {
                $end = new \DateTime($end);
            } else {
                throw new \Exception('Query parameter "end" should be a string');
            }

            $filters = $request->get('filters', '{}');
            $filters = match (true) {
                \is_array($filters) => $filters,
                \is_string($filters) => json_decode($filters, true),
                default => false,
            };

            if (!\is_array($filters)) {
                throw new \Exception('Query parameter "filters" is not valid');
            }
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $setDataEvent = $this->eventDispatcher->dispatch(new SetDataEvent($start, $end, $filters));

        $content = $this->serializer->serialize($setDataEvent->getEvents());

        return new JsonResponse(
            $content,
            empty($content) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK,
        );
    }
}
