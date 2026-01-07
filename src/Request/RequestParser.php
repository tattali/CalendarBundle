<?php

declare(strict_types=1);

namespace CalendarBundle\Request;

use CalendarBundle\Exception\InvalidDateException;
use CalendarBundle\Exception\InvalidJsonException;
use Symfony\Component\HttpFoundation\Request;

class RequestParser implements RequestParserInterface
{
    private const MAX_JSON_DEPTH = 4;

    public function parse(Request $request): QueryParameters
    {
        return new QueryParameters(
            $this->parseDate($request, 'start'),
            $this->parseDate($request, 'end'),
            $this->parseFilters($request),
        );
    }

    private function parseDate(Request $request, string $parameter): \DateTime
    {
        $value = $request->query->getString($parameter);

        if ('' === $value) {
            throw InvalidDateException::missingParameter($parameter);
        }

        try {
            return new \DateTime($value);
        } catch (\DateMalformedStringException $e) {
            throw InvalidDateException::forParameter($parameter, $e);
        }
    }

    /**
     * @return mixed[]
     */
    private function parseFilters(Request $request): array
    {
        $value = $request->query->getString('filters', '{}');

        try {
            /** @var mixed[] $filters */
            $filters = json_decode($value, associative: true, depth: self::MAX_JSON_DEPTH, flags: \JSON_THROW_ON_ERROR);

            return $filters;
        } catch (\JsonException $e) {
            throw InvalidJsonException::forParameter('filters', $e);
        }
    }
}
