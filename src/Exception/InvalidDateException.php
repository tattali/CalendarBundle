<?php

declare(strict_types=1);

namespace CalendarBundle\Exception;

class InvalidDateException extends \InvalidArgumentException implements CalendarExceptionInterface
{
    public static function forParameter(string $parameter, ?\Throwable $previous = null): self
    {
        return new self(
            \sprintf('Query parameter "%s" is not a valid date', $parameter),
            previous: $previous,
        );
    }

    public static function missingParameter(string $parameter): self
    {
        return new self(
            \sprintf('Query parameter "%s" is required', $parameter),
        );
    }
}
