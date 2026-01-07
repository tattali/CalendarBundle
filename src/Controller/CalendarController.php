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
    ) {}

    private const DEFAULT_MAX_AGE = 300; // 5 minutes

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

        $etag = hash('xxh3', $content);
        $response = JsonResponse::fromJsonString($content);

        $response->setETag($etag);
        $response->setPublic();
        $response->setMaxAge(self::DEFAULT_MAX_AGE);

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }
}
