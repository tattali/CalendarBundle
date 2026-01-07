<?php

declare(strict_types=1);

namespace CalendarBundle\Exception;

class InvalidJsonException extends \InvalidArgumentException implements CalendarExceptionInterface
{
    public static function forParameter(string $parameter, ?\Throwable $previous = null): self
    {
        return new self(
            \sprintf('Query parameter "%s" is not valid JSON', $parameter),
            previous: $previous,
        );
    }
}
