<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Fixtures;

use CalendarBundle\Request\QueryParameters;
use CalendarBundle\Request\RequestParserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Decorates the bundle's request parser, as an application would.
 */
final class CountingRequestParser implements RequestParserInterface
{
    public int $calls = 0;

    public function __construct(
        private readonly RequestParserInterface $inner,
    ) {
    }

    public function parse(Request $request): QueryParameters
    {
        ++$this->calls;

        return $this->inner->parse($request);
    }
}
