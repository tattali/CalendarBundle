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
            $start = $this->getParameter($request, 'start');
            if ($start && \is_string($start)) {
                $start = new \DateTime($start);
            } else {
                throw new \UnexpectedValueException('Query parameter "start" should be a string');
            }

            $end = $this->getParameter($request, 'end');
            if ($end && \is_string($end)) {
                $end = new \DateTime($end);
            } else {
                throw new \UnexpectedValueException('Query parameter "end" should be a string');
            }

            $filters = $this->getParameter($request, 'filters', '{}');
            $filters = match (true) {
                \is_array($filters) => $filters,
                \is_string($filters) => json_decode($filters, true),
                default => false,
            };

            if (!\is_array($filters)) {
                throw new \UnexpectedValueException('Query parameter "filters" is not valid');
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

    /**
     * Reads a parameter the way Request::get() does, without its deprecation in Symfony 7.4:
     * the request attributes first, then the query string, then the request body.
     */
    private function getParameter(Request $request, string $key, mixed $default = null): mixed
    {
        foreach ([$request->attributes, $request->query, $request->request] as $parameters) {
            if ($parameters->has($key)) {
                return $parameters->all()[$key];
            }
        }

        return $default;
    }
}
