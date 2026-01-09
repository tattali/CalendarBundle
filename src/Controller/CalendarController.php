<?php

declare(strict_types=1);

namespace CalendarBundle\Controller;

use CalendarBundle\Event\SetDataEvent;
use CalendarBundle\Exception\CalendarExceptionInterface;
use CalendarBundle\Request\RequestParserInterface;
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
        private readonly RequestParserInterface $requestParser,
        private readonly int $cacheMaxAge = 300,
    ) {}

    public function load(Request $request): JsonResponse
    {
        try {
            $params = $this->requestParser->parse($request);
        } catch (CalendarExceptionInterface $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        $setDataEvent = $this->eventDispatcher->dispatch(
            new SetDataEvent($params->start, $params->end, $params->filters),
        );

        $content = $this->serializer->serialize($setDataEvent->getEvents());

        if ('' === $content) {
            $response = new JsonResponse();
            $response->setStatusCode(Response::HTTP_NO_CONTENT);
            $response->setContent('');

            return $response;
        }

        $response = JsonResponse::fromJsonString($content);

        if ($this->cacheMaxAge > 0) {
            $response->setETag(hash('xxh3', $content));
            $response->setPublic();
            $response->setMaxAge($this->cacheMaxAge);
        }

        return $response;
    }
}
