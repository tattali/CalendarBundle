<?php

declare(strict_types=1);

namespace CalendarBundle\Request;

use CalendarBundle\Exception\CalendarExceptionInterface;
use Symfony\Component\HttpFoundation\Request;

interface RequestParserInterface
{
    /**
     * Parse and validate query parameters from a request.
     *
     * @throws CalendarExceptionInterface when validation fails
     */
    public function parse(Request $request): QueryParameters;
}
