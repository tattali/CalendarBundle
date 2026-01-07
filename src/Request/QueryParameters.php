<?php

declare(strict_types=1);

namespace CalendarBundle\Request;

/**
 * Value object holding parsed and validated query parameters.
 */
final readonly class QueryParameters
{
    /**
     * @param mixed[] $filters
     */
    public function __construct(
        public \DateTime $start,
        public \DateTime $end,
        public array $filters,
    ) {}
}
