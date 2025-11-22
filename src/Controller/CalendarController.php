<?php

declare(strict_types=1);

namespace CalendarBundle\Controller;

use CalendarBundle\Event\SetDataEvent;
use CalendarBundle\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CalendarController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SerializerInterface $serializer,
    ) {}

    public function load(Request $request): JsonResponse
    {
        try {
            $start = $request->query->get('start');
            if ($start && \is_string($start)) {
                try {
                    $start = new \DateTime($start);
                } catch (\DateMalformedStringException $e) {
                    throw new \UnexpectedValueException('Query parameter "start" is not a valid date', previous: $e);
                }
            } else {
                throw new \UnexpectedValueException('Query parameter "start" should be a string');
            }

            $end = $request->query->get('end');
            if ($end && \is_string($end)) {
                try {
                    $end = new \DateTime($end);
                } catch (\DateMalformedStringException $e) {
                    throw new \UnexpectedValueException('Query parameter "end" is not a valid date', previous: $e);
                }
            } else {
                throw new \UnexpectedValueException('Query parameter "end" should be a string');
            }

            try {
                $filters = $request->query->getString('filters', '{}');
                $filters = json_decode($filters, true, flags: \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \UnexpectedValueException('Query parameter "filters" is not a valid JSON', previous: $e);
            }
        } catch (\UnexpectedValueException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $setDataEvent = $this->eventDispatcher->dispatch(new SetDataEvent($start, $end, $filters));

        $content = $this->serializer->serialize($setDataEvent->getEvents());

        return JsonResponse::fromJsonString(
            $content,
            empty($content) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK,
        );
    }
}
