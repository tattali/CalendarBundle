<?php

declare(strict_types=1);

namespace CalendarBundle\Request;

use CalendarBundle\Exception\InvalidDateException;
use CalendarBundle\Exception\InvalidJsonException;
use CalendarBundle\Exception\InvalidTimeZoneException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

class RequestParser implements RequestParserInterface
{
    /**
     * ISO 8601 calendar dates as FullCalendar sends them: a date, or a date
     * and time (minutes, optional seconds and fraction) with "Z", a
     * "+hh:mm" or "-hh:mm" offset, or no offset.
     */
    private const string ISO_8601_DATE = '/^(?<year>\d{4})-(?<month>0[1-9]|1[0-2])-(?<day>0[1-9]|[12]\d|3[01])(?:T(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d(?:\.\d{1,6})?)?(?:Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)?)?$/D';

    /** @var int<1, 512> */
    private readonly int $jsonMaxDepth;

    /**
     * @param int<1, 512> $jsonMaxDepth
     */
    public function __construct(
        int $jsonMaxDepth = 4,
    ) {
        $this->jsonMaxDepth = $jsonMaxDepth;
    }

    public function parse(Request $request): QueryParameters
    {
        $timeZone = $this->parseTimeZone($request);

        return new QueryParameters(
            $this->parseDate($request, 'start', $timeZone),
            $this->parseDate($request, 'end', $timeZone),
            $this->parseFilters($request),
            $timeZone,
        );
    }

    private function parseTimeZone(Request $request): ?\DateTimeZone
    {
        $value = $this->input($request)->getString('timeZone');

        if ('' === $value) {
            return null;
        }

        if (str_contains($value, "\0")) {
            throw InvalidTimeZoneException::forParameter('timeZone');
        }

        try {
            return new \DateTimeZone($value);
        } catch (\DateInvalidTimeZoneException $e) {
            throw InvalidTimeZoneException::forParameter('timeZone', $e);
        }
    }

    private function parseDate(Request $request, string $parameter, ?\DateTimeZone $timeZone = null): \DateTimeImmutable
    {
        $value = $this->input($request)->getString($parameter);

        if ('' === $value) {
            throw InvalidDateException::missingParameter($parameter);
        }

        if (1 !== preg_match(self::ISO_8601_DATE, $value, $parts)
            || !checkdate((int) $parts['month'], (int) $parts['day'], (int) $parts['year'])) {
            throw InvalidDateException::forParameter($parameter);
        }

        try {
            $date = new \DateTimeImmutable($value, $timeZone);
        } catch (\DateMalformedStringException $e) {
            throw InvalidDateException::forParameter($parameter, $e);
        }

        if (null !== $timeZone) {
            $date = $date->setTimezone($timeZone);
        }

        return $date;
    }

    /**
     * @return mixed[]
     */
    private function parseFilters(Request $request): array
    {
        $value = $this->input($request)->getString('filters', '{}');

        try {
            $filters = json_decode($value, associative: true, depth: $this->jsonMaxDepth, flags: \JSON_THROW_ON_ERROR);
            if (!\is_array($filters)) {
                throw InvalidJsonException::mustBeObjectOrArray('filters');
            }

            return $filters;
        } catch (\JsonException $e) {
            throw InvalidJsonException::forParameter('filters', $e);
        }
    }

    /**
     * @return InputBag<string|int|float|bool|null>
     */
    private function input(Request $request): InputBag
    {
        return $request->isMethod('GET') || $request->isMethod('HEAD') ? $request->query : $request->request;
    }
}
