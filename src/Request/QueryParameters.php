<?php

declare(strict_types=1);

namespace CalendarBundle\Request;

/**
 * Value object holding parsed and validated query parameters.
 */
final readonly class QueryParameters
{
    public \DateTimeImmutable $start;

    public \DateTimeImmutable $end;

    /**
     * A \DateTime is copied into a \DateTimeImmutable; an immutable date is
     * stored as is.
     *
     * @param mixed[] $filters
     */
    public function __construct(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        public array $filters,
        public ?\DateTimeZone $timeZone = null,
    ) {
        $this->start = $start instanceof \DateTimeImmutable ? $start : \DateTimeImmutable::createFromInterface($start);
        $this->end = $end instanceof \DateTimeImmutable ? $end : \DateTimeImmutable::createFromInterface($end);
    }
}
